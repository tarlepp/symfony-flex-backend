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
#   digest      Show multi-platform index digest and pin reference for image
#   verify      Verify that PHP, nginx and MariaDB Dockerfile base images are
#               multi-platform and that production and development
#               Dockerfiles use the same base images
#   platforms   Show platforms that the current buildx builder supports
#   setup-qemu  Install QEMU emulators for cross-platform builds
#   build       Build image for a single platform and load it locally
#   push        Build multi-platform image and push it to a registry
#
# Inputs are read from environment variables. When a value is missing and the
# script is run in an interactive terminal, the value is asked from the user.
#
#   IMAGE       Image reference, e.g. `php:8.5.11-fpm-bookworm` (digest)
#   PLATFORM    Single target platform, e.g. `linux/arm64` (build)
#   PLATFORMS   Comma separated platforms (setup-qemu, push)
#   DOCKERFILE  Dockerfile path, e.g. `Dockerfile` (build, push)
#   TAG         Image tag to create (build, push)
#   BUILDER     buildx builder name for multi-platform builds (push)
#
# Requirements:
#   - docker with buildx plugin (host machine)
#   - jq
#
################################################################################

set -euo pipefail

if [ -n "${NO_COLOR:-}" ]; then
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

PINNED_DOCKERFILES=(Dockerfile Dockerfile_dev docker/*/Dockerfile*)
VERIFY_DOCKERFILES=(
    Dockerfile
    Dockerfile_dev
    docker/nginx/Dockerfile
    docker/nginx/Dockerfile_dev
    docker/mariadb/Dockerfile
)
PLATFORM_PATTERN='^linux/[a-z0-9]+(/v[0-9]+)?$'

usage() {
    cat << EOF
Docker multi-platform image helper, see doc/DOCKER_MULTI_PLATFORM.md

Usage:
  $0 <command>

Commands:
  digest      Show multi-platform index digest and pin reference for image
  verify      Verify that PHP, nginx and MariaDB Dockerfile base images are
              multi-platform and that production and development
              Dockerfiles use the same base images
  platforms   Show platforms that the current buildx builder supports
  setup-qemu  Install QEMU emulators for cross-platform builds
  build       Build image for a single platform and load it locally
  push        Build multi-platform image and push it to a registry

Inputs are read from environment variables. When a value is missing and the
script is run in an interactive terminal, the value is asked from the user.

  IMAGE       Image reference, e.g. php:8.5.11-fpm-bookworm (digest)
  PLATFORM    Single target platform, e.g. linux/arm64 (build)
  PLATFORMS   Comma separated platforms (setup-qemu, push)
  DOCKERFILE  Dockerfile path, e.g. Dockerfile (build, push)
  TAG         Image tag to create (build, push)
  BUILDER     buildx builder name for multi-platform builds (push)

Make targets:
  make docker-image-digest
  make docker-image-verify-pins
  make docker-builder-platforms
  make docker-setup-qemu
  make docker-build-platform
  make docker-build-multi-platform
EOF
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

# Fetch raw manifest (index or single manifest) with a single registry request.
raw_manifest() {
    docker buildx imagetools inspect --raw "$1"
}

media_type() {
    jq -r 'if .manifests then (.mediaType // "application/vnd.oci.image.index.v1+json") else (.mediaType // "application/vnd.oci.image.manifest.v1+json") end' <<< "$1"
}

image_platforms() {
    jq -r '[.manifests[]?.platform
        | select(. != null and .os != "unknown")
        | .os + "/" + .architecture + (if .variant then "/" + .variant else "" end)]
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

    raw="$(raw_manifest "$IMAGE")"
    type="$(media_type "$raw")"
    platforms="$(image_platforms "$raw")"
    digest="$(docker buildx imagetools inspect "$IMAGE" --format '{{.Manifest.Digest}}')"
    name="${IMAGE%@*}"

    printf '\n'
    printf 'Media type: %s\n' "$type"
    printf 'Digest:     %s\n' "$digest"
    printf 'Platforms:  %s\n' "${platforms:-single platform image}"
    printf '\n'

    if ! is_index "$type"; then
        warning "WARNING" "This is a single platform manifest, not a multi-platform index."
        warning "WARNING" "Do not use this digest if the image must work on multiple platforms."
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
        FILE_REFS[$file]+="${ref}"$'\n'
        printf '  line %s: %s\n' "$line_number" "$ref"

        if ! [[ "$ref" =~ @sha256:[a-f0-9]{64}$ ]]; then
            printf '  %sWARN%s  Not pinned with digest, uses tag (multi-platform if upstream is)\n' "$YELLOW" "$NC"
            warnings=$((warnings + 1))
            continue
        fi

        if [ -z "${MANIFEST_CACHE[$ref]+set}" ]; then
            if ! raw="$(raw_manifest "$ref" 2>&1)"; then
                printf '  %sFAIL%s  Could not inspect image: %s\n' "$RED" "$NC" "$(tail -n 1 <<< "$raw")"
                status=1
                continue
            fi

            MANIFEST_CACHE[$ref]="$raw"
        fi

        raw="${MANIFEST_CACHE[$ref]}"
        type="$(media_type "$raw")"
        platforms="$(image_platforms "$raw")"

        if is_index "$type"; then
            printf '  %sOK%s    Multi-platform: %s\n' "$GREEN" "$NC" "$platforms"
        else
            printf '  %sFAIL%s  Single platform digest, replace with index digest (make docker-image-digest)\n' "$RED" "$NC"
            status=1
        fi
    done < <(grep -niE '^[[:space:]]*FROM[[:space:]]' "$file")

    if [ "$found" -eq 0 ]; then
        printf '  %sFAIL%s  No FROM instruction found\n' "$RED" "$NC"
        status=1
    fi

    printf '\n'

    if [ "$status" -eq 0 ] && [ "$warnings" -gt 0 ]; then
        return 2
    fi

    return "$status"
}

image_digest() {
    if [[ "$1" =~ @(sha256:[a-f0-9]{64})$ ]]; then
        printf '%s' "${BASH_REMATCH[1]}"
    fi
}

# Compare base images of production Dockerfile and its `_dev` counterpart.
# Usage: verify_pair <production file> <development file>
# Returns: 0 = same base images, 1 = mismatch
verify_pair() {
    local prod="$1"
    local dev="$2"
    local label_width=$((${#dev} + 1))
    local prod_refs dev_refs common only_prod only_dev ref digest

    prod_refs="$(printf '%s' "${FILE_REFS[$prod]:-}" | sort -u)"
    dev_refs="$(printf '%s' "${FILE_REFS[$dev]:-}" | sort -u)"

    printf '%s%s <-> %s%s\n' "$BOLD" "$prod" "$dev" "$NC"

    if [ -z "$prod_refs" ] || [ -z "$dev_refs" ]; then
        printf '  %sFAIL%s  No base images found to compare\n\n' "$RED" "$NC"
        return 1
    fi

    common="$(comm -12 <(printf '%s\n' "$prod_refs") <(printf '%s\n' "$dev_refs"))"
    only_prod="$(comm -23 <(printf '%s\n' "$prod_refs") <(printf '%s\n' "$dev_refs"))"
    only_dev="$(comm -13 <(printf '%s\n' "$prod_refs") <(printf '%s\n' "$dev_refs"))"

    while read -r ref; do
        [ -z "$ref" ] && continue
        digest="$(image_digest "$ref")"

        if [ -n "$digest" ]; then
            printf '  %sOK%s    Same digest: %s (%s)\n' "$GREEN" "$NC" "$digest" "${ref%@*}"
        else
            printf '  %sOK%s    Same image: %s\n' "$GREEN" "$NC" "$ref"
        fi
    done <<< "$common"

    if [ -z "$only_prod" ] && [ -z "$only_dev" ]; then
        printf '\n'
        return 0
    fi

    printf '  %sFAIL%s  Base images differ, use the same pin reference in both files\n' "$RED" "$NC"

    while read -r ref; do
        [ -n "$ref" ] && printf '        %-*s %s\n' "$label_width" "$prod:" "$ref"
    done <<< "$only_prod"

    while read -r ref; do
        [ -n "$ref" ] && printf '        %-*s %s\n' "$label_width" "$dev:" "$ref"
    done <<< "$only_dev"

    printf '\n'

    return 1
}

command_verify() {
    local file prod width=0 failures=0 warnings=0 result
    local -A results=()
    local -a pairs=()
    declare -gA MANIFEST_CACHE=()
    declare -gA FILE_REFS=()

    info "OK" "Verifying base images of ${#VERIFY_DOCKERFILES[@]} Dockerfiles"
    printf '\n'

    for file in "${VERIFY_DOCKERFILES[@]}"; do
        [ "${#file}" -gt "$width" ] && width="${#file}"

        result=0
        verify_dockerfile "$file" || result=$?

        case "$result" in
            0) results[$file]="${GREEN}OK${NC}" ;;
            2)
                results[$file]="${YELLOW}OK (with warnings)${NC}"
                warnings=$((warnings + 1))
                ;;
            *)
                results[$file]="${RED}FAIL${NC}"
                failures=$((failures + 1))
                ;;
        esac
    done

    # Production and development Dockerfiles must use the same base images
    for file in "${VERIFY_DOCKERFILES[@]}"; do
        [[ "$file" == *_dev ]] || continue

        prod="${file%_dev}"

        [[ " ${VERIFY_DOCKERFILES[*]} " == *" ${prod} "* ]] && pairs+=("$prod")
    done

    if [ "${#pairs[@]}" -gt 0 ]; then
        info "OK" "Comparing base images of production and development Dockerfiles"
        printf '\n'
    fi

    for prod in "${pairs[@]}"; do
        file="${prod} <-> ${prod}_dev"
        [ "${#file}" -gt "$width" ] && width="${#file}"

        if verify_pair "$prod" "${prod}_dev"; then
            results[$file]="${GREEN}OK${NC}"
        else
            results[$file]="${RED}FAIL${NC}"
            failures=$((failures + 1))
        fi
    done

    printf '%sSummary%s\n' "$BOLD" "$NC"
    printf '  Multi-platform base images:\n'

    for file in "${VERIFY_DOCKERFILES[@]}"; do
        printf '    %-*s  %s\n' "$width" "$file" "${results[$file]}"
    done

    if [ "${#pairs[@]}" -gt 0 ]; then
        printf '  Production and development use same base images:\n'
    fi

    for prod in "${pairs[@]}"; do
        file="${prod} <-> ${prod}_dev"
        printf '    %-*s  %s\n' "$width" "$file" "${results[$file]}"
    done

    printf '\n'

    if [ "$failures" -gt 0 ]; then
        error "${failures} check(s) failed, see doc/DOCKER_MULTI_PLATFORM.md"
        exit 1
    fi

    if [ "$warnings" -gt 0 ]; then
        warning "OK" "All Dockerfiles use multi-platform base images, ${warnings} with unpinned tags"
        return 0
    fi

    info "OK" "All Dockerfiles use multi-platform base images and production/development pins match"
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

    info "OK" "Building ${TAG} from ${DOCKERFILE} for ${PLATFORM}"

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
