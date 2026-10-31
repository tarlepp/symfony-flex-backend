#!/bin/bash

################################################################################
# Docker multi-platform image helper
#
# Helper commands for maintaining multi-platform Docker base image pins and for
# building images for other platforms. See doc/DOCKER_MULTI_PLATFORM.md.
#
# Usage:
#   ./scripts/docker-images.sh <command>
#
# Commands:
#   check-updates  Report newer stable image versions (Docker Hub)
#   digest      Show multi-platform index digest and pin reference for image
#   verify      Verify that PHP, nginx and MariaDB Dockerfile base images are
#               multi-platform
#   platforms   Show platforms that the current buildx builder supports
#   setup-qemu  Install QEMU emulators for cross-platform builds
#   build       Build image for a single platform and load it locally
#   push        Build multi-platform image and push it to a registry
#
# Inputs are read from environment variables. When a value is missing and the
# script is run in an interactive terminal, the value is asked from the user.
# check-updates defaults to external images in project Dockerfiles.
#
#   IMAGE       Image reference (digest), or optional single image (check-updates)
#   PLATFORM    Single target platform, e.g. `linux/arm64` (build)
#   PLATFORMS   Comma separated platforms (setup-qemu, push)
#   DOCKERFILE  Dockerfile path, e.g. `Dockerfile` (build, push)
#   TARGET      Build stage, defaults to the last stage (build, push)
#   TAG         Image tag to create (build, push)
#   BUILDER     buildx builder name for multi-platform builds (push)
#
# Requirements:
#   - docker with buildx plugin (host machine)
#   - jq
#   - curl (check-updates only)
#
################################################################################

set -euo pipefail

if [ -n "${NO_COLOR:-}" ] || [ "${COLOR:-auto}" = never ] ||
    { [ "${COLOR:-auto}" = auto ] && { [ ! -t 2 ] || [ "${TERM:-}" = dumb ]; }; }; then
    RED=''
    GREEN=''
    YELLOW=''
    BOLD=''
    NC=''
else
    RED=$'\033[0;31m'
    GREEN=$'\033[0;32m'
    YELLOW=$'\033[1;33m'
    BOLD=$'\033[1m'
    NC=$'\033[0m'
fi

PINNED_DOCKERFILES=(Dockerfile docker/*/Dockerfile*)
VERIFY_DOCKERFILES=(
    Dockerfile
    docker/nginx/Dockerfile
    docker/mariadb/Dockerfile
)
PLATFORM_PATTERN='^linux/[a-z0-9]+(/v[0-9]+)?$'

usage() {
    cat << EOF
Docker multi-platform image helper, see doc/DOCKER_MULTI_PLATFORM.md

Usage:
  $0 <command>

Commands:
  check-updates  Report newer stable image versions (Docker Hub)
  digest         Show multi-platform index digest and pin reference for image
  verify         Verify that PHP, nginx and MariaDB Dockerfile base images are
                 multi-platform
  platforms      Show platforms that the current buildx builder supports
  setup-qemu     Install QEMU emulators for cross-platform builds
  build          Build image for a single platform and load it locally
  push           Build multi-platform image and push it to a registry

Inputs are read from environment variables. When a value is missing and the
script is run in an interactive terminal, the value is asked from the user.
check-updates defaults to external FROM and COPY --from images in Dockerfile
and docker/*/Dockerfile*. It reports stable versions, including major upgrades,
preserving the tag variant. It groups results by Dockerfile with verified multi-platform
pin references for updates. It requires curl, jq and Docker with buildx, and
changes no files. Exit status: 0 = no updates or errors, 1 = updates or errors,
69 = missing tools. Floating and prerelease tags are skipped explicitly.

  IMAGE       Image reference (digest), or optional single image (check-updates)
  PLATFORM    Single target platform, e.g. linux/arm64 (build)
  PLATFORMS   Comma separated platforms (setup-qemu, push)
  DOCKERFILE  Dockerfile path, e.g. Dockerfile (build, push)
  TARGET      Build stage, defaults to the last stage (build, push)
  TAG         Image tag to create (build, push)
  BUILDER     buildx builder name for multi-platform builds (push)

Make targets:
  make docker-image-check-updates
  make docker-image-digest
  make docker-image-verify-pins
  make docker-builder-platforms
  make docker-setup-qemu
  make docker-build-platform
  make docker-build-multi-platform
EOF
}

# List registry references, excluding earlier stages, numeric stages and scratch.
dockerfile_images() {
    awk '
        {
            sub(/\r$/, "")
            if ($0 ~ /^[[:space:]]*(#|$)/) next
            if (sub(/\\[[:space:]]*$/, "")) {
                instruction = instruction $0
                next
            }
            $0 = instruction $0
            instruction = ""
        }
        toupper($1) == "FROM" {
            i = 2
            while ($i ~ /^--/) i++
            ref = $i
            if (tolower($(i + 1)) == "as") stages[tolower($(i + 2))] = 1
            if (!(tolower(ref) in stages) && tolower(ref) != "scratch") print ref
        }
        toupper($1) == "COPY" {
            for (i = 2; i <= NF; i++) {
                if ($i ~ /^--from=/) {
                    ref = substr($i, 8)
                    if (!(tolower(ref) in stages) && ref !~ /^[0-9]+$/) print ref
                    break
                }
            }
        }
    ' "$1"
}

# Fetch every page: Docker Hub orders tags by publication date, not version.
hub_tags() {
    local repository="$1" suffix="$2" page next
    local -A visited=()
    local endpoint="https://hub.docker.com/v2/repositories/${1}/tags/"
    local url="${endpoint}?page_size=100"

    if [ -n "$suffix" ]; then
        url+="&name=$(jq -rn --arg value "$suffix" '$value | @uri')"
    fi

    while [ -n "$url" ]; do
        if [ -n "${visited[$url]+set}" ]; then
            error "Repeated Docker Hub pagination URL for ${repository}"
            return 1
        fi
        visited[$url]=1
        if ! page="$(curl --fail --silent --show-error --retry 3 --connect-timeout 10 --max-time 60 "$url")"; then
            error "Could not fetch Docker Hub tags for ${repository}"
            return 1
        fi

        if ! jq -e '(.results | type == "array") and
            (all(.results[]; .name | type == "string")) and
            (.next == null or (.next | type == "string"))' > /dev/null <<< "$page"; then
            error "Invalid Docker Hub tag response for ${repository}"
            return 1
        fi

        jq -r '.results[].name' <<< "$page"
        next="$(jq -r '.next // empty' <<< "$page")"
        if [ -n "$next" ] && [[ "$next" != "$endpoint"?* ]]; then
            error "Unexpected Docker Hub pagination URL for ${repository}"
            return 1
        fi
        url="$next"
    done
}

update_pin_reference() {
    local image="$1" digest raw type platforms

    if ! digest="$(inspect_image "$image" --format '{{.Manifest.Digest}}')"; then
        error "Could not resolve index digest for ${image}: $(tail -n 1 <<< "$digest")"
        return 1
    fi
    if ! [[ "$digest" =~ ^sha256:[a-f0-9]{64}$ ]]; then
        error "Invalid index digest for ${image}: ${digest}"
        return 1
    fi
    # Inspect the resolved digest so a moving tag cannot mismatch the printed pin.
    if ! raw="$(raw_manifest "${image}@${digest}")"; then
        error "Could not inspect ${image}@${digest}: $(tail -n 1 <<< "$raw")"
        return 1
    fi
    if ! type="$(media_type "$raw")" || ! platforms="$(image_platforms "$raw")"; then
        error "Invalid image manifest for ${image}@${digest}"
        return 1
    fi
    if ! is_index "$type"; then
        error "${image}: single platform manifest, no multi-platform pin available"
        return 1
    fi
    if [[ "$platforms" != *" "* ]]; then
        error "${image}: index contains fewer than two distinct runnable platforms (${platforms:-none})"
        return 1
    fi

    printf '  Multi-platform pin: %s@%s\n' "$image" "$digest"
    printf '  Platforms: %s\n' "$platforms"
}

# Internal outcomes: 0 current, 1 failed, 2 update, 3 skipped, 4 update with pin failure.
check_image_update() {
    local image="${1%@*}" repository tag suffix prefix current tags latest first
    local pattern='^(v?)((0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*))(-[a-zA-Z0-9_.-]+)?$'

    if [[ "${image##*/}" != *:* ]]; then
        warning "SKIP" "${image}: no explicit version tag"
        return 3
    fi

    repository="${image%:*}"
    tag="${image##*:}"
    if ! [[ "$tag" =~ $pattern ]]; then
        warning "SKIP" "${image}: tag is not a full stable semantic version with an optional variant"
        return 3
    fi
    prefix="${BASH_REMATCH[1]}"
    current="${BASH_REMATCH[2]}"
    suffix="${BASH_REMATCH[6]}"
    if [[ "$suffix" =~ ^-(alpha|beta|rc|RC|preview|dev)([0-9.-]|$) ]]; then
        warning "SKIP" "${image}: prerelease tag"
        return 3
    fi

    case "$repository" in
        docker.io/*) repository="${repository#docker.io/}" ;;
        index.docker.io/*) repository="${repository#index.docker.io/}" ;;
    esac
    first="${repository%%/*}"
    if [[ "$repository" == */* ]] && [[ "$first" == *.* || "$first" == *:* || "$first" = localhost ]]; then
        error "${image}: version lookup currently supports Docker Hub only"
        return 1
    fi
    [[ "$repository" == */* ]] || repository="library/${repository}"
    if ! [[ "$repository" =~ ^[a-z0-9_-]+/[a-z0-9_.-]+$ ]]; then
        error "${image}: invalid Docker Hub repository"
        return 1
    fi

    if ! tags="$(hub_tags "$repository" "$suffix")"; then
        return 1
    fi

    latest="$(jq -Rrs --arg suffix "$suffix" --arg prefix "$prefix" '
        [split("\n")[] |
            capture("^(?<prefix>v?)(?<version>(0|[1-9][0-9]*)\\.(0|[1-9][0-9]*)\\.(0|[1-9][0-9]*))(?<suffix>-[a-zA-Z0-9_.-]+)?$") |
            select(.prefix == $prefix and (.suffix // "") == $suffix) |
            {version: .version, parts: (.version | split(".") | map(tonumber))}]
        | sort_by(.parts) | last | .version // empty' <<< "$tags")"
    if [ -z "$latest" ]; then
        error "${image}: no matching stable version tags found"
        return 1
    fi

    if jq -en --arg current "$current" --arg latest "$latest" \
        '($latest | split(".") | map(tonumber)) > ($current | split(".") | map(tonumber))' > /dev/null; then
        warning "UPDATE" "${image} -> ${image%:*}:${prefix}${latest}${suffix}"
        update_pin_reference "${image%:*}:${prefix}${latest}${suffix}" || return 4
        return 2
    else
        info "OK" "${image}: no newer stable version"
    fi
}

command_check_updates() {
    local file image references tool heading="" issues=0 updates=0 skipped=0 status=0
    local -A seen=() results=()

    require_docker

    for tool in curl jq; do
        if ! command -v "$tool" > /dev/null 2>&1; then
            error "${tool} command not found, install it first"
            return 69
        fi
    done

    references=""
    for file in "${PINNED_DOCKERFILES[@]}"; do
        [ -f "$file" ] || continue
        while IFS= read -r image; do
            image="${image%@*}"
            if [ -n "${IMAGE:-}" ] && [ "$image" != "${IMAGE%@*}" ]; then
                continue
            fi
            references+="${file}"$'\t'"${image}"$'\n'
        done < <(dockerfile_images "$file")
    done

    if [ -n "${IMAGE:-}" ] && [ -z "$references" ]; then
        references="IMAGE input (no matching Dockerfile)"$'\t'"${IMAGE%@*}"
    fi
    references="${references%$'\n'}"
    if [ -z "$references" ]; then
        error "No external Dockerfile images found; run from the project root or set IMAGE"
        return 1
    fi

    while IFS=$'\t' read -r file image; do
        [ -z "${seen[$file|$image]+set}" ] || continue
        seen[$file|$image]=1
        if [ "$heading" != "$file" ]; then
            [ -z "$heading" ] || printf '\n'
            printf '%s%s%s\n' "$BOLD" "$file" "$NC"
            heading="$file"
        fi
        if [ -z "${results[$image]+set}" ]; then
            status=0
            results[$image]="$(check_image_update "$image")" || status=$?
            if [ "$status" -eq 2 ]; then
                updates=$((updates + 1))
            elif [ "$status" -eq 4 ]; then
                updates=$((updates + 1))
                issues=$((issues + 1))
            elif [ "$status" -eq 3 ]; then
                skipped=$((skipped + 1))
            elif [ "$status" -ne 0 ]; then
                issues=$((issues + 1))
            fi
        fi
        printf '%s\n' "${results[$image]}" | sed 's/^/  /'
    done <<< "$references"

    if [ "$issues" -gt 0 ]; then
        printf '\n%s[CHECK]%s %s image check(s) failed; %s image update(s) available\n' "$RED" "$NC" "$issues" "$updates" >&2
        return 1
    elif [ "$updates" -gt 0 ]; then
        printf '\n%s[CHECK]%s %s image(s) require attention: updates available\n' "$YELLOW" "$NC" "$updates" >&2
        return 1
    elif [ "$skipped" -gt 0 ]; then
        printf '\n%s[SKIP]%s %s image version check(s) skipped; no updates found among completed checks\n' "$YELLOW" "$NC" "$skipped" >&2
    else
        printf '\n%s[OK]%s Image version checks completed; no updates available\n' "$GREEN" "$NC" >&2
    fi
}

error() {
    printf '%sERROR:%s %s\n' "$RED" "$NC" "$1" >&2
}

info() {
    printf '%s[%s]%s %s\n' "$GREEN" "$1" "$NC" "$2"
}

warning() {
    printf '%s[%s]%s %s\n' "$YELLOW" "$1" "$NC" "$2"
}

# Read value from environment variable or ask it from the user.
# Usage: ask <variable name> <question> [default value]
ask() {
    local name="$1"
    local question="$2"
    local default="${3:-}"
    local value="${!name:-}"

    if [ -z "$value" ]; then
        if [ ! -t 0 ]; then
            if [ -n "$default" ]; then
                value="$default"
            else
                error "${name} is required (e.g. make <target> ${name}=...)"
                exit 64
            fi
        else
            if [ -n "$default" ]; then
                read -r -p "${question} [${default}]: " value
            else
                read -r -p "${question}: " value
            fi

            value="${value:-$default}"
        fi
    fi

    value="$(printf '%s' "$value" | tr -d '[:space:]')"

    if [ -z "$value" ]; then
        error "${name} cannot be empty"
        exit 64
    fi

    printf -v "$name" '%s' "$value"
}

confirm() {
    local answer

    if [ ! -t 0 ]; then
        error "Confirmation required, run this command in an interactive terminal"
        exit 64
    fi

    read -r -p "${YELLOW}$1 [y/N]${NC} " answer

    [ "$answer" = "y" ] || [ "$answer" = "Y" ]
}

validate_platforms() {
    local platform
    local list

    IFS=',' read -r -a list <<< "$1"

    for platform in "${list[@]}"; do
        if ! [[ "$platform" =~ $PLATFORM_PATTERN ]]; then
            error "Invalid platform '${platform}', expected format like 'linux/arm64'"
            exit 64
        fi
    done
}

require_docker() {
    if ! command -v docker > /dev/null 2>&1; then
        error "docker command not found, run this command on the host machine"
        exit 69
    fi

    if ! docker buildx version > /dev/null 2>&1; then
        error "docker buildx plugin is not available"
        exit 69
    fi

    if ! command -v jq > /dev/null 2>&1; then
        error "jq command not found, install it first"
        exit 69
    fi
}

# Retries transient registry errors (rate limit, timeouts, server errors).
# Prints inspection output on success, or registry error on failure.
inspect_image() {
    local attempt output delay remaining hint rate_limit_notified=0

    for attempt in 1 2 3; do
        if output="$(docker buildx imagetools inspect "$@" 2>&1)"; then
            printf '%s' "$output"
            return 0
        fi

        if [ "$rate_limit_notified" -eq 0 ] && grep -qiE '429|toomanyrequests' <<< "$output"; then
            warning "RATE LIMIT" "Docker Hub returned 429 Too Many Requests. Run 'docker login docker.io' or wait for the limit to reset; CI should authenticate before running this check." >&2
            rate_limit_notified=1
        fi

        if [ "$attempt" -eq 3 ] || ! grep -qiE '429|toomanyrequests|timeout|EOF|50[0-9] ' <<< "$output"; then
            break
        fi

        delay=$((attempt * 10))
        hint=""
        if [ "$rate_limit_notified" -eq 1 ]; then
            hint=" Press Ctrl+C to stop, then run 'docker login docker.io'."
        fi
        for ((remaining = delay; remaining > 0; remaining -= 5)); do
            printf '  %sRETRY%s Registry request failed; attempt %s/3 in %ss.%s\n' \
                "$YELLOW" "$NC" "$((attempt + 1))" "$remaining" "$hint" >&2
            sleep 5 || return $?
        done
        printf '  %sRETRY%s Starting registry request attempt %s/3\n' \
            "$YELLOW" "$NC" "$((attempt + 1))" >&2
    done

    printf '%s\n' "$output"
    return 1
}

raw_manifest() {
    inspect_image --raw "$1"
}

github_escape_data() {
    local value="$1"

    value="${value//%/%25}"
    value="${value//$'\r'/%0D}"
    value="${value//$'\n'/%0A}"

    printf '%s' "$value"
}

github_escape_property() {
    local value

    value="$(github_escape_data "$1")"
    value="${value//:/%3A}"
    value="${value//,/%2C}"

    printf '%s' "$value"
}

# Emit GitHub Actions annotation, shown inline in pull request diffs.
# Usage: github_annotation <error|warning> <file> <line or empty> <title> <message>
github_annotation() {
    local properties

    [ "${GITHUB_ACTIONS:-}" = "true" ] || return 0

    properties="file=$(github_escape_property "$2")"
    [ -n "$3" ] && properties+=",line=$3"
    properties+=",title=$(github_escape_property "$4")"

    printf '::%s %s::%s\n' "$1" "$properties" "$(github_escape_data "$5")"
}

# Append markdown to GitHub Actions job summary.
github_summary() {
    [ -n "${GITHUB_STEP_SUMMARY:-}" ] || return 0

    printf '%s\n' "$@" >> "$GITHUB_STEP_SUMMARY"
}

media_type() {
    jq -r 'if .manifests then (.mediaType // "application/vnd.oci.image.index.v1+json") else (.mediaType // "application/vnd.oci.image.manifest.v1+json") end' <<< "$1"
}

image_platforms() {
    jq -r '[.manifests[]?
        | select(.annotations["vnd.docker.reference.type"] != "attestation-manifest")
        | .platform
        | select((.os | type) == "string" and (.architecture | type) == "string")
        | select(.os != "" and .os != "unknown" and .architecture != "" and .architecture != "unknown")
        | .os + "/" + .architecture + (if .variant then "/" + .variant else "" end)]
        | unique
        | join(" ")' <<< "$1"
}

is_index() {
    case "$1" in
        *index*|*manifest.list*) return 0 ;;
        *) return 1 ;;
    esac
}

command_digest() {
    local raw type digest platforms name repository

    ask IMAGE "Image name and tag (e.g. php:8.5.11-fpm-bookworm)"

    info "OK" "Inspecting ${IMAGE}"

    if ! raw="$(raw_manifest "$IMAGE")"; then
        error "Could not inspect ${IMAGE}: $(tail -n 1 <<< "$raw")"
        exit 1
    fi

    type="$(media_type "$raw")"
    platforms="$(image_platforms "$raw")"
    if ! digest="$(inspect_image "$IMAGE" --format '{{.Manifest.Digest}}')"; then
        error "Could not resolve index digest for ${IMAGE}: $(tail -n 1 <<< "$digest")"
        exit 1
    fi
    name="${IMAGE%@*}"

    printf '\n'
    printf 'Media type: %s\n' "$type"
    printf 'Digest:     %s\n' "$digest"
    printf 'Platforms:  %s\n' "${platforms:-none listed}"
    printf '\n'

    if ! is_index "$type"; then
        warning "WARNING" "This is a single platform manifest, not a multi-platform index."
        warning "WARNING" "Do not use this digest if the image must work on multiple platforms."
        exit 1
    fi

    if [[ "$platforms" != *" "* ]]; then
        warning "WARNING" "This image index contains fewer than two distinct runnable platforms (${platforms:-none})."
        warning "WARNING" "Do not pin this digest for multi-platform builds; wait for upstream to publish the platform images or choose another tag."
        exit 1
    fi

    printf 'Pin reference:\n\n    FROM %s@%s\n\n' "$name" "$digest"

    repository="${name%%:*}"

    if grep -nE "^FROM ${repository}[:@]" "${PINNED_DOCKERFILES[@]}" 2> /dev/null; then
        printf '\n'
        info "INFO" "Lines above use the same image, update those to the new pin reference"
    fi
}

# Verify base images of a single Dockerfile and print result per `FROM` line.
# Usage: verify_dockerfile <file>
# Returns: 0 = all OK, 1 = problems found, 2 = OK with warnings
verify_dockerfile() {
    local file="$1"
    local -A stages=()
    local line_number line word ref raw type platforms is_stage status=0 found=0 warnings=0
    local -a words

    printf '%s%s%s\n' "$BOLD" "$file" "$NC"

    if [ ! -f "$file" ]; then
        printf '  %sFAIL%s  File not found\n\n' "$RED" "$NC"
        github_annotation error "$file" "" "Dockerfile not found" "${file} is listed for base image verification but does not exist"
        IMAGE_ROWS+=("| \`${file}\` | - | - | :x: File not found |")
        return 1
    fi

    while IFS=: read -r line_number line; do
        read -r -a words <<< "$line"

        # Skip `FROM` flags like `--platform=...` to get the image reference
        ref=""
        for word in "${words[@]:1}"; do
            [[ "$word" == --* ]] && continue
            ref="$word"
            break
        done

        is_stage=0
        [ -n "${stages[${ref,,}]:-}" ] && is_stage=1

        if [ "${#words[@]}" -ge 4 ] && [ "${words[-2],,}" = "as" ]; then
            stages["${words[-1],,}"]=1
        fi

        # Reference to an earlier build stage, not a registry image
        [ "$is_stage" -eq 1 ] && continue

        found=1
        printf '  line %s: %s\n' "$line_number" "$ref"

        if ! [[ "$ref" =~ @sha256:[a-f0-9]{64}$ ]]; then
            printf '  %sWARN%s  Not pinned with digest, uses tag (multi-platform if upstream is)\n' "$YELLOW" "$NC"
            github_annotation warning "$file" "$line_number" "Base image not pinned" "${ref} is not pinned with a digest. Use: make docker-image-digest IMAGE=${ref}"
            IMAGE_ROWS+=("| \`${file}\` | ${line_number} | \`${ref}\` | :warning: Not pinned with digest |")
            warnings=$((warnings + 1))
            continue
        fi

        if [ -z "${MANIFEST_CACHE[$ref]+set}" ]; then
            if ! raw="$(raw_manifest "$ref")"; then
                printf '  %sFAIL%s  Could not inspect image: %s\n' "$RED" "$NC" "$(tail -n 1 <<< "$raw")"
                github_annotation error "$file" "$line_number" "Base image could not be inspected" "${ref}: $(tail -n 1 <<< "$raw")"
                IMAGE_ROWS+=("| \`${file}\` | ${line_number} | \`${ref}\` | :x: Could not inspect image |")
                status=1
                continue
            fi

            MANIFEST_CACHE[$ref]="$raw"
        fi

        raw="${MANIFEST_CACHE[$ref]}"
        type="$(media_type "$raw")"
        platforms="$(image_platforms "$raw")"

        if ! is_index "$type"; then
            printf '  %sFAIL%s  Single platform digest, replace with index digest (make docker-image-digest)\n' "$RED" "$NC"
            github_annotation error "$file" "$line_number" "Single platform base image digest" "${ref} points to a single platform manifest. Replace it with the multi-platform index digest from: make docker-image-digest IMAGE=${ref%@*}"
            IMAGE_ROWS+=("| \`${file}\` | ${line_number} | \`${ref}\` | :x: Single platform digest |")
            status=1
        elif [[ "$platforms" != *" "* ]]; then
            printf '  %sFAIL%s  Image index contains fewer than two distinct runnable platforms (%s)\n' "$RED" "$NC" "${platforms:-none}"
            github_annotation error "$file" "$line_number" "Insufficient base image platforms" "${ref} contains fewer than two distinct runnable platforms (${platforms:-none}). Wait for upstream platform images or choose another tag and digest."
            IMAGE_ROWS+=("| \`${file}\` | ${line_number} | \`${ref}\` | :x: Fewer than two runnable platforms (${platforms:-none}) |")
            status=1
        else
            printf '  %sOK%s    Multi-platform: %s\n' "$GREEN" "$NC" "$platforms"
            IMAGE_ROWS+=("| \`${file}\` | ${line_number} | \`${ref}\` | :white_check_mark: ${platforms} |")
        fi
    done < <(grep -niE '^[[:space:]]*FROM[[:space:]]' "$file")

    if [ "$found" -eq 0 ]; then
        printf '  %sFAIL%s  No FROM instruction found\n' "$RED" "$NC"
        github_annotation error "$file" "" "No FROM instruction" "${file} does not contain any FROM instruction"
        status=1
    fi

    printf '\n'

    if [ "$status" -eq 0 ] && [ "$warnings" -gt 0 ]; then
        return 2
    fi

    return "$status"
}

doc_link() {
    local path="doc/DOCKER_MULTI_PLATFORM.md"

    if [ -n "${GITHUB_SERVER_URL:-}" ] && [ -n "${GITHUB_REPOSITORY:-}" ] && [ -n "${GITHUB_SHA:-}" ]; then
        printf '[%s](%s/%s/blob/%s/%s)' "$path" "$GITHUB_SERVER_URL" "$GITHUB_REPOSITORY" "$GITHUB_SHA" "$path"
    else
        printf '`%s`' "$path"
    fi
}

# Write verification result to GitHub Actions job summary.
# Uses `plain` results of the caller (command_verify).
# Usage: write_verify_summary <failures> <warnings>
write_verify_summary() {
    local failures="$1"
    local warnings="$2"
    local file

    [ -n "${GITHUB_STEP_SUMMARY:-}" ] || return 0

    github_summary "## Docker base image pins" ""

    if [ "$failures" -gt 0 ]; then
        github_summary ":x: **${failures} check(s) failed.** See $(doc_link) how to fix those." ""
    elif [ "$warnings" -gt 0 ]; then
        github_summary ":warning: All base images are multi-platform, ${warnings} Dockerfile(s) with unpinned tags." ""
    else
        github_summary ":white_check_mark: All base images are multi-platform." ""
    fi

    github_summary "| Multi-platform base images | Result |" "|---|---|"

    for file in "${VERIFY_DOCKERFILES[@]}"; do
        github_summary "| \`${file}\` | ${plain[$file]} |"
    done

    github_summary "" "<details><summary>Base images</summary>" "" "| File | Line | Image | Result |" "|---|---|---|---|"
    github_summary "${IMAGE_ROWS[@]}"
    github_summary "" "</details>"
}

command_verify() {
    local file width=0 failures=0 warnings=0 result
    local -A results=()
    declare -gA MANIFEST_CACHE=()
    declare -ga IMAGE_ROWS=()
    local -A plain=()

    info "OK" "Verifying base images of ${#VERIFY_DOCKERFILES[@]} Dockerfiles"
    printf '\n'

    for file in "${VERIFY_DOCKERFILES[@]}"; do
        [ "${#file}" -gt "$width" ] && width="${#file}"

        result=0
        verify_dockerfile "$file" || result=$?

        case "$result" in
            0)
                results[$file]="${GREEN}OK${NC}"
                plain[$file]=":white_check_mark: OK"
                ;;
            2)
                results[$file]="${YELLOW}OK (with warnings)${NC}"
                plain[$file]=":warning: OK (with warnings)"
                warnings=$((warnings + 1))
                ;;
            *)
                results[$file]="${RED}FAIL${NC}"
                plain[$file]=":x: FAIL"
                failures=$((failures + 1))
                ;;
        esac
    done

    printf '%sSummary%s\n' "$BOLD" "$NC"
    printf '  Multi-platform base images:\n'

    for file in "${VERIFY_DOCKERFILES[@]}"; do
        printf '    %-*s  %s\n' "$width" "$file" "${results[$file]}"
    done

    printf '\n'

    write_verify_summary "$failures" "$warnings"

    if [ "$failures" -gt 0 ]; then
        error "${failures} check(s) failed, see doc/DOCKER_MULTI_PLATFORM.md"
        exit 1
    fi

    if [ "$warnings" -gt 0 ]; then
        warning "OK" "All Dockerfiles use multi-platform base images, ${warnings} with unpinned tags"
        return 0
    fi

    info "OK" "All Dockerfiles use multi-platform base images"
}

command_platforms() {
    docker buildx inspect --bootstrap | awk '/^(Name|Driver|Platforms):/ && !seen[$1]++'
}

command_setup_qemu() {
    ask PLATFORMS "Emulators to install (comma separated, e.g. arm64,amd64 or all)" "arm64,amd64"

    if ! [[ "$PLATFORMS" =~ ^[a-z0-9,]+$ ]]; then
        error "Invalid emulator list '${PLATFORMS}'"
        exit 64
    fi

    warning "CAUTION" "This runs a privileged container (tonistiigi/binfmt) that registers QEMU emulators on the host."

    if ! confirm "Install QEMU emulators '${PLATFORMS}'?"; then
        info "SKIP" "Nothing installed"
        return 0
    fi

    docker run --privileged --rm tonistiigi/binfmt --install "$PLATFORMS"
}

build_args() {
    if [ -n "${TARGET:-}" ]; then
        printf -- '--target\n%s\n' "$TARGET"
    fi

    if grep -qE '^ARG HOST_UID' "$1"; then
        printf -- '--build-arg\nHOST_UID=%s\n--build-arg\nHOST_GID=%s\n' "$(id -u)" "$(id -g)"
    fi
}

ask_dockerfile() {
    ask DOCKERFILE "Dockerfile" "Dockerfile"

    if [ ! -f "$DOCKERFILE" ]; then
        error "Dockerfile '${DOCKERFILE}' not found"
        exit 66
    fi

    local stages final_stage
    stages="$(awk 'toupper($1) == "FROM" && toupper($(NF-1)) == "AS" {print $NF}' "$DOCKERFILE")"
    final_stage="$(awk 'toupper($1) == "FROM" {stage = toupper($(NF-1)) == "AS" ? $NF : ""} END {print stage}' "$DOCKERFILE")"

    if [ -n "$final_stage" ]; then
        ask TARGET "Build target ($(echo "$stages" | paste -sd,))" "$final_stage"
    fi

    if [ -n "${TARGET:-}" ] && ! grep -Fxq -- "$TARGET" <<< "$stages"; then
        error "Build target '${TARGET}' not found in '${DOCKERFILE}'"
        exit 64
    fi
}

command_build() {
    local args=()

    ask PLATFORM "Target platform (e.g. linux/amd64, linux/arm64)" "linux/arm64"

    if [[ "$PLATFORM" == *,* ]]; then
        error "Only one platform is supported, use 'make docker-build-multi-platform' for multiple"
        exit 64
    fi

    validate_platforms "$PLATFORM"
    ask_dockerfile
    ask TAG "Image tag" "symfony-flex-backend:$(printf '%s' "$PLATFORM" | cut -d/ -f2-  | tr '/' '-')"

    mapfile -t args < <(build_args "$DOCKERFILE")

    info "OK" "Building ${TAG} from ${DOCKERFILE}${TARGET:+ (target ${TARGET})} for ${PLATFORM}"

    docker buildx build \
        --platform "$PLATFORM" \
        --file "$DOCKERFILE" \
        --tag "$TAG" \
        "${args[@]}" \
        --load \
        "$(dirname "$DOCKERFILE")"
}

command_push() {
    local args=()

    ask PLATFORMS "Target platforms (comma separated)" "linux/amd64,linux/arm64"
    validate_platforms "$PLATFORMS"
    ask_dockerfile
    ask TAG "Image tag including registry (e.g. registry.example.com/symfony-flex-backend:latest)"
    ask BUILDER "buildx builder name" "multi-platform"

    if ! confirm "Build ${DOCKERFILE} for ${PLATFORMS} and push it as ${TAG}?"; then
        info "SKIP" "Nothing built"
        return 0
    fi

    if ! docker buildx inspect "$BUILDER" > /dev/null 2>&1; then
        info "OK" "Creating buildx builder '${BUILDER}' (docker-container driver)"
        docker buildx create --name "$BUILDER" --driver docker-container > /dev/null
    fi

    mapfile -t args < <(build_args "$DOCKERFILE")

    docker buildx build \
        --builder "$BUILDER" \
        --platform "$PLATFORMS" \
        --file "$DOCKERFILE" \
        --tag "$TAG" \
        "${args[@]}" \
        --push \
        "$(dirname "$DOCKERFILE")"
}

case "${1:-}" in
    check-updates ) command_check_updates ;;
    digest ) require_docker; command_digest ;;
    verify ) require_docker; command_verify ;;
    platforms ) require_docker; command_platforms ;;
    setup-qemu ) require_docker; command_setup_qemu ;;
    build ) require_docker; command_build ;;
    push ) require_docker; command_push ;;
    ""|-h|--help|help ) usage ;;
    * )
        error "Unknown command '${1}'"
        printf '\n' >&2
        usage >&2
        exit 64
        ;;
esac
