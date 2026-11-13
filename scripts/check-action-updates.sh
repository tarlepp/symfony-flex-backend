#!/bin/bash

################################################################################
# GitHub Actions Update Checker
#
# This script checks all pinned GitHub Actions in this repository for available
# updates and displays the new SHA for each outdated action.
#
# It discovers actions and workflow tool pins from:
#   - .github/workflows/*.yml files
#   - .github/actions/*/action.yml files
#   - scripts/lint-workflows.sh tool-pin declarations
#
# Usage:
#   ./scripts/check-action-updates.sh
#   ./scripts/check-action-updates.sh --current-pins-md
#   ./scripts/check-action-updates.sh --tools-only
#
# Requirements:
#   - git (for git ls-remote)
#
# Security Note:
#   This script helps identify updates but does NOT automatically apply them.
#   Always review changelog and test updates before merging.
#
################################################################################

# Collect discovery and lookup failures so remaining checks still run.
set +e
source "$(dirname "$0")/command-output.sh"

checker_outcome() {
    if [ "${ACTION_OUTPUT_WRAPPED:-0}" != 1 ]; then
        output_result "$@"
    fi
}

# Command overrides
GIT_BIN="${GIT_BIN:-git}"
TIMEOUT_BIN="${TIMEOUT_BIN:-timeout}"
WORKFLOW_DIR="${WORKFLOW_DIR:-.github/workflows}"
ACTIONS_DIR="${ACTIONS_DIR:-.github/actions}"
TOOL_PINS_FILE="${TOOL_PINS_FILE:-scripts/lint-workflows.sh}"

MODE="check"
case "${1:-}" in
    "" ) ;;
    --current-pins-md ) MODE="current-pins-md" ;;
    --tools-only ) MODE="tools-only" ;;
    * )
        printf 'Usage: %s [--current-pins-md|--tools-only]\n' "$0" >&2
        exit 64
        ;;
esac

GREEN=$OUTPUT_GREEN
YELLOW=$OUTPUT_YELLOW
NC=$OUTPUT_RESET

# Discovery state, keyed by owner/repository[/action-path]@version.
declare -A ACTIONS=()
declare -A ACTION_SOURCES=()
declare -A ACTION_REFS=()
declare -A TOOL_KEYS=()

# Check results use the same keys; source locations stay in ACTION_SOURCES.
declare -A ACTION_RESULTS=()
declare -A ACTION_LATEST=()
declare -A ACTION_LATEST_SHAS=()

# Track statistics
TOTAL=0
CURRENT=0
OUTDATED=0
DISCOVERY_WARNINGS=0

discover_actions_in_file() {
    local file=$1 line line_number=0 uses_ref comment_version repo ref version
    local action_key existing_ref source

    while IFS= read -r line; do
        line_number=$((line_number + 1))
        [[ "$line" == *uses:* ]] || continue

        uses_ref=$(printf '%s\n' "$line" |
            sed -n 's/.*uses:[[:space:]]*\([^[:space:]#][^[:space:]#]*\).*/\1/p')
        comment_version=$(printf '%s\n' "$line" |
            sed -n 's/.*#[[:space:]]*\([^[:space:]]\+\).*/\1/p')

        [ -n "$uses_ref" ] || continue

        case "$uses_ref" in
            ./*|../*|*/.github/workflows/*) continue ;;
        esac

        repo="${uses_ref%@*}"
        ref="${uses_ref#*@}"

        [[ "$repo" == */* ]] || continue

        source="$file:$line_number"

        version=''

        if [ -n "$comment_version" ]; then
            version="$comment_version"
        elif printf '%s\n' "$ref" | grep -Eq '^v?[0-9]+(\.|$)'; then
            version="$ref"
        fi

        if ! printf '%s\n' "$ref" | grep -Eq '^[0-9a-f]{40}$'; then
            output_status WARN "Unpinned action reference at $source -> $uses_ref"
            DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
        fi

        if [ -z "$version" ]; then
            output_status WARN "Cannot determine version for $repo at $source (add '# vX.Y.Z' comment)"
            DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
            continue
        fi

        action_key="$repo@$version"
        existing_ref="${ACTION_REFS[$action_key]}"

        if [ -n "$existing_ref" ] && [ "$existing_ref" != "$ref" ]; then
            output_status WARN "Conflicting pinned refs detected for $action_key: $existing_ref (${ACTION_SOURCES[$action_key]}) vs $ref ($source)"
            DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
            continue
        fi

        ACTIONS["$action_key"]="$version"
        ACTION_REFS["$action_key"]="$ref"

        if [ -n "${ACTION_SOURCES[$action_key]}" ]; then
            ACTION_SOURCES["$action_key"]+=$'\n'"$source"
        else
            ACTION_SOURCES["$action_key"]="$source"
        fi
    done < "$file"
}

discover_actions() {
    local file

    if [ ! -d "$WORKFLOW_DIR" ]; then
        output_status WARN "Workflow directory not found: $WORKFLOW_DIR"
        return
    fi

    for file in "$WORKFLOW_DIR"/*.yml; do
        [ -f "$file" ] || continue

        discover_actions_in_file "$file"
    done

    if [ ! -d "$ACTIONS_DIR" ]; then
        output_status WARN "Actions directory not found: $ACTIONS_DIR"
        return
    fi

    for file in "$ACTIONS_DIR"/*/action.yml; do
        [ -f "$file" ] || continue

        discover_actions_in_file "$file"
    done
}

print_current_pins_markdown() {
    local action version ref ref_display source

    printf '| Action | Version | Ref | Source |\n'
    printf '|--------|---------|-----|--------|\n'

    while IFS= read -r action; do
        [ -n "$action" ] || continue

        version="${ACTIONS[$action]}"
        ref="${ACTION_REFS[$action]}"
        source="${ACTION_SOURCES[$action]}"

        if [ -n "${TOOL_KEYS[$action]}" ]; then
            ref_display="\`$ref\`"
        elif printf '%s\n' "$ref" | grep -Eq '^[0-9a-f]{40}$'; then
            ref_display="\`${ref:0:8}\`"
        else
            ref_display="\`$ref\`"
        fi

        printf '| %s | %s | %s | `%s` |\n' "$action" "$version" "$ref_display" "$source"
    done < <(printf '%s\n' "${!ACTIONS[@]}" | sort)
}

discover_tools() {
    local line number=0 version repo ref key

    if [ ! -f "$TOOL_PINS_FILE" ]; then
        printf 'Tool pin file not found: %s\n' "$TOOL_PINS_FILE" >&2
        DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
        return
    fi

    while IFS= read -r line; do
        number=$((number + 1))

        [[ "$line" == *"# tool-pin:"* ]] || continue

        if [[ ! "$line" =~ ^[A-Z_]+_TAG=(v[0-9]+\.[0-9]+\.[0-9]+)[[:space:]]+#\ tool-pin:\ ([a-zA-Z0-9_.-]+/[a-zA-Z0-9_.-]+)@([0-9a-f]{40})$ ]]; then
            printf 'Invalid tool pin at %s:%s\n' "$TOOL_PINS_FILE" "$number" >&2
            DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
            continue
        fi

        version="${BASH_REMATCH[1]}"
        repo="${BASH_REMATCH[2]}"
        ref="${BASH_REMATCH[3]}"
        key="$repo@$version"

        if [ -n "${ACTION_REFS[$key]}" ] && [ "${ACTION_REFS[$key]}" != "$ref" ]; then
            printf 'Conflicting tool pins for %s\n' "$key" >&2
            DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
            continue
        fi

        ACTIONS["$key"]="$version"
        ACTION_REFS["$key"]="$ref"
        ACTION_SOURCES["$key"]="$TOOL_PINS_FILE:$number"
        TOOL_KEYS["$key"]=1
    done < "$TOOL_PINS_FILE"

    if [ "${#TOOL_KEYS[@]}" -eq 0 ]; then
        printf 'No workflow tool pins discovered in %s\n' "$TOOL_PINS_FILE" >&2
        DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
    fi
}

lookup_action_tags() {
    local seconds=$1 context=$2 errors refs status
    shift 2

    if ! errors=$(mktemp); then
        output_status FAIL "$context (cannot create temporary Git diagnostic file)"
        return 1
    fi

    refs=$("$TIMEOUT_BIN" "$seconds" "$GIT_BIN" ls-remote --tags "$@" 2>"$errors")
    status=$?

    if [ "$status" -ne 0 ]; then
        if [ "$status" -eq 124 ]; then
            output_status FAIL "$context (GitHub lookup timed out after ${seconds}s; check network/DNS connectivity and retry)"
        else
            output_status FAIL "$context (git ls-remote failed with exit $status)"
        fi

        if [ -s "$errors" ]; then
            sed 's/^/   Git diagnostic: /' "$errors" >&2
        fi
    fi

    rm -f "$errors"

    [ "$status" -eq 0 ] || return "$status"

    printf '%s\n' "$refs"
}

tag_commit() {
    local tag=$1

    # Annotated tags resolve to their peeled commit; lightweight tags use the ref.
    awk -v ref="refs/tags/$tag" '
        $2 == ref { plain = $1 }
        $2 == ref "^{}" { peeled = $1 }
        END { print peeled ? peeled : plain }
    '
}

check_tool() {
    local key=$1 repo=$2 current=$3 refs latest sha

    TOTAL=$((TOTAL + 1))

    if ! refs=$(lookup_action_tags 20 "Unable to fetch tool tags: $repo" "https://github.com/$repo.git"); then
        DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
        return
    fi

    sha=$(tag_commit "$current" <<< "$refs")

    if [ "$sha" != "${ACTION_REFS[$key]}" ]; then
        output_status FAIL "Tool tag/SHA mismatch: $key (${ACTION_SOURCES[$key]}); expected ${ACTION_REFS[$key]}, resolved ${sha:-missing}"
        DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
        return
    fi

    latest=$(printf '%s\n' "$refs" | awk '{sub(/^refs\/tags\//, "", $2); print $2}' | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -n 1)

    if [ -z "$latest" ]; then
        output_status FAIL "No stable tool tags found: $repo"
        DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
        return
    fi

    if [ "$latest" != "$current" ]; then
        sha=$(tag_commit "$latest" <<< "$refs")
        printf '%s[WARN]%s UPDATE AVAILABLE (tool): %s %s -> %s\n  Commit SHA: %s\n  Source: %s\n  Verify release asset SHA-256 checksums before updating.\n' "$YELLOW" "$NC" "$repo" "$current" "$latest" "$sha" "${ACTION_SOURCES[$key]}"
        OUTDATED=$((OUTDATED + 1))
    else
        printf '%s[OK]%s Tool up-to-date: %s %s @ %s (tag verified)\n' "$GREEN" "$NC" "$repo" "$current" "$sha"
        CURRENT=$((CURRENT + 1))
    fi
}

latest_action_version() {
    local action="$1"
    local current_version="$2"
    local tags latest repo_path

    # Extract owner/repo (first two parts) for git ls-remote, handles both:
    # - actions/checkout
    # - github/codeql-action/init
    repo_path=$(printf '%s\n' "$action" | cut -d/ -f1-2)

    if ! tags=$(lookup_action_tags 10 "Unable to fetch action version: $action @ $current_version" --refs "https://github.com/$repo_path.git"); then
        return 1
    fi

    tags=$(printf '%s\n' "$tags" | awk '{print $2}' | sed 's#refs/tags/##')

    # Get the absolute latest version tag (semver-like only, at least X.Y to exclude
    # floating major alias tags like v1, v2 and non-version tags like "verbose")
    latest=$(printf '%s\n' "$tags" | grep -E '^v?[0-9]+\.[0-9]+' | sort -V | tail -n 1)

    if [ -z "$latest" ]; then
        output_status FAIL "Unable to fetch action version: $action @ $current_version (GitHub lookup succeeded, but no version-like tags matching vX.Y were found)"
        return 1
    fi

    printf '%s\n' "$latest"
}

check_action() {
    local key=$1 repo=$2 current=${ACTIONS[$1]} latest refs sha repo_path

    TOTAL=$((TOTAL + 1))

    if ! latest=$(latest_action_version "$repo" "$current"); then
        DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
        ACTION_RESULTS["$key"]=version-error
        return
    fi

    repo_path=$(printf '%s\n' "$repo" | cut -d/ -f1-2)

    if ! refs=$(lookup_action_tags 10 "Unable to fetch action SHA: $repo @ $latest" \
        "https://github.com/$repo_path.git" "refs/tags/$latest" "refs/tags/$latest^{}"); then
        DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
        ACTION_RESULTS["$key"]=commit-error
        return
    fi

    sha=$(tag_commit "$latest" <<< "$refs")

    if ! printf '%s\n' "$sha" | grep -Eq '^[0-9a-f]{40}$'; then
        output_status FAIL "Unable to fetch action SHA: $repo @ $latest (GitHub lookup succeeded, but the exact tag resolved to a missing or invalid 40-character commit SHA)"
        DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
        ACTION_RESULTS["$key"]=commit-error
        return
    fi

    ACTION_LATEST["$key"]="$latest"
    ACTION_LATEST_SHAS["$key"]="$sha"

    if [ "${latest#v}" != "${current#v}" ] || [ "$sha" != "${ACTION_REFS[$key]}" ]; then
        ACTION_RESULTS["$key"]=update
        OUTDATED=$((OUTDATED + 1))
    else
        ACTION_RESULTS["$key"]=current
        CURRENT=$((CURRENT + 1))
    fi
}

print_action_update() {
    local key=$1 current=${ACTIONS[$1]} latest=${ACTION_LATEST[$1]}
    local sha=${ACTION_LATEST_SHAS[$1]} source prefix='      Source: '

    printf '   %s -> %s\n' "$current" "$latest"

    if [ "${current#v}" = "${latest#v}" ]; then
        printf '      Pin differs from tag commit (same version)\n'
        printf '      Pinned SHA: %s\n' "${ACTION_REFS[$key]}"
    fi

    printf '      SHA:   %s\n' "$sha"
    printf '      Short: %s\n' "${sha:0:8}"

    while IFS= read -r source; do
        [ -n "$source" ] || continue

        printf '%s%s\n' "$prefix" "$source"
        prefix='              '
    done <<< "${ACTION_SOURCES[$key]}"
}

print_repository_results() {
    local repo=$1 key has_update=false all_current=true

    shift

    [ "$#" -gt 0 ] || return

    for key in "$@"; do
        case "${ACTION_RESULTS[$key]}" in
            update) has_update=true; all_current=false ;;
            current) ;;
            *) all_current=false ;;
        esac
    done

    # Lookup failures already have diagnostics; do not label them up-to-date.
    if [ "$has_update" = true ]; then
        printf '%s[WARN]%s UPDATE AVAILABLE: %s\n' "$YELLOW" "$NC" "$repo"

        for key in "$@"; do
            case "${ACTION_RESULTS[$key]}" in
                current) printf '   %s (up-to-date)\n' "${ACTIONS[$key]}" ;;
                version-error) printf '   %s -> (unable to fetch)\n' "${ACTIONS[$key]}" ;;
                commit-error) printf '   %s -> (error fetching SHA)\n' "${ACTIONS[$key]}" ;;
                update) print_action_update "$key" ;;
            esac
        done

        printf '\n'
    elif [ "$all_current" = true ]; then
        printf '%s[OK]%s %s\n' "$GREEN" "$NC" "$repo"

        for key in "$@"; do
            printf '   %s (up-to-date)\n' "${ACTIONS[$key]}"
        done

        printf '\n'
    fi
}

check_repositories() {
    local key repo
    local -A repository_keys=()
    local -a checked_actions=()

    # Newline-separated keys retain each action's version and optional subpath.
    for key in "${!ACTIONS[@]}"; do
        repo="${key%@*}"
        repository_keys["$repo"]+="$key"$'\n'
    done

    while IFS= read -r repo; do
        checked_actions=()

        while IFS= read -r key; do
            [ -n "$key" ] || continue

            if [ -n "${TOOL_KEYS[$key]}" ]; then
                check_tool "$key" "$repo" "${ACTIONS[$key]}"
            else
                check_action "$key" "$repo"
                checked_actions+=("$key")
            fi
        done <<< "${repository_keys[$repo]}"

        print_repository_results "$repo" "${checked_actions[@]}"
    done < <(printf '%s\n' "${!repository_keys[@]}" | sort)
}

print_summary() {
    output_status INFO 'Action/tool update summary'
    printf 'Actions/tools checked:   %s\n' "$TOTAL"
    printf 'Discovery warnings:      %s%s%s\n' "$YELLOW" "$DISCOVERY_WARNINGS" "$NC"
    printf 'Up-to-date:              %s%s%s\n' "$GREEN" "$CURRENT" "$NC"
    printf 'Updates available:       %s%s%s\n' "$YELLOW" "$OUTDATED" "$NC"
    printf '\n'

    if [ "$OUTDATED" -gt 0 ]; then
        printf '%sTo update actions:%s\n' "$YELLOW" "$NC"
        printf '1. Review the new versions and their changelogs\n'
        printf '2. Follow the update process in doc/ACTIONS_UPDATE.md\n'
        printf '3. Test changes in a feature branch\n'
        printf '4. Create a PR and verify CI passes\n'
        printf '\n'
    fi
}

finish_checks() {
    if [ "$DISCOVERY_WARNINGS" -gt 0 ]; then
        checker_outcome FAIL 'Discovery or lookup checks failed. Review the failed entries above; resolve pin inconsistencies or retry failed GitHub lookups.'
        return 2
    fi

    if [ "$OUTDATED" -gt 0 ]; then
        checker_outcome WARN "$OUTDATED action/tool update(s) available"
        return 1
    fi

    checker_outcome OK 'All checked actions and tools are up-to-date'
}

main() {
    if [ "$MODE" != "current-pins-md" ] && [ "${ACTION_OUTPUT_WRAPPED:-0}" != 1 ]; then
        output_status RUN 'GitHub Actions and workflow tool updates'
    fi

    if [ "$MODE" != "tools-only" ]; then
        discover_actions
    fi

    discover_tools

    if [ "$DISCOVERY_WARNINGS" -gt 0 ] && [ "$MODE" = "check" ]; then
        output_status WARN "Discovery warnings found: $DISCOVERY_WARNINGS"
        printf '\n'
        output_status INFO 'Continuing with version checks so all findings are shown in this run'
        printf '\n'
    fi

    if [ "${#ACTIONS[@]}" -eq 0 ]; then
        if [ "$DISCOVERY_WARNINGS" -gt 0 ]; then
            checker_outcome FAIL 'Discovery checks failed; no pinned actions or tools could be checked'
            return 2
        fi

        checker_outcome SKIP "No pinned GitHub Actions discovered from $WORKFLOW_DIR or $ACTIONS_DIR"
        return 0
    fi

    if [ "$MODE" = "current-pins-md" ]; then
        print_current_pins_markdown
        [ "$DISCOVERY_WARNINGS" -eq 0 ] || return 2
        return 0
    fi

    printf 'Checking %s GitHub Actions and workflow tools for updates...\n\n' "${#ACTIONS[@]}"

    check_repositories
    print_summary
    finish_checks
}

main
exit $?
