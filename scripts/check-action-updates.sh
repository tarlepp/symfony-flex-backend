#!/bin/bash

################################################################################
# GitHub Actions Update Checker
#
# This script checks all pinned GitHub Actions in this repository for available
# updates and displays the new SHA for each outdated action.
#
# Usage:
#   ./scripts/check-action-updates.sh
#   ./scripts/check-action-updates.sh --current-pins-md
#
# Requirements:
#   - git (for git ls-remote)
#
# Security Note:
#   This script helps identify updates but does NOT automatically apply them.
#   Always review changelog and test updates before merging.
#
################################################################################

# Don't exit on error - handle errors gracefully
set +e

# Command overrides
GIT_BIN="${GIT_BIN:-git}"
TIMEOUT_BIN="${TIMEOUT_BIN:-timeout}"
WORKFLOW_DIR="${WORKFLOW_DIR:-.github/workflows}"

MODE="check"
case "${1:-}" in
    "" ) ;;
    --current-pins-md ) MODE="current-pins-md" ;;
    * )
        printf 'Usage: %s [--current-pins-md]\n' "$0" >&2
        exit 64
        ;;
esac

# Colors for output
if [ -n "${NO_COLOR:-}" ]; then
    RED=''
    GREEN=''
    YELLOW=''
    BLUE=''
    NC=''
else
    RED=$'\033[0;31m'
    GREEN=$'\033[0;32m'
    YELLOW=$'\033[1;33m'
    BLUE=$'\033[0;34m'
    NC=$'\033[0m' # No Color
fi

# Tracked GitHub Actions are discovered dynamically from workflow files.
declare -A ACTIONS=()
declare -A ACTION_SOURCES=()
declare -A ACTION_REFS=()

# Track statistics
TOTAL=0
CURRENT=0
OUTDATED=0
DISCOVERY_WARNINGS=0

print_separator() {
    printf '%s=================================================================================%s\n' "$BLUE" "$NC"
}

print_centered_banner() {
    local text="$1"
    local width=81
    local pad=$(( (width - ${#text}) / 2 ))

    print_separator
    printf '%s%*s%s%s\n' "$BLUE" "$pad" '' "$text" "$NC"
    print_separator
    printf '\n'
}

discover_actions() {
    local workflow line uses_ref version repo existing_version ref comment_version existing_ref
    local line_number discovered_version

    if [ ! -d "$WORKFLOW_DIR" ]; then
        printf '%s⚠️  Workflow directory not found: %s%s\n' "$YELLOW" "$WORKFLOW_DIR" "$NC" >&2
        return
    fi

    for workflow in "$WORKFLOW_DIR"/*.yml; do
        [ -f "$workflow" ] || continue

        line_number=0
        while IFS= read -r line; do
            line_number=$((line_number + 1))

            case "$line" in
                *uses:*) ;;
                *) continue ;;
            esac

            uses_ref=$(printf '%s\n' "$line" | sed -n 's/.*uses:[[:space:]]*\([^[:space:]#][^[:space:]#]*\).*/\1/p')
            comment_version=$(printf '%s\n' "$line" | sed -n 's/.*#[[:space:]]*\([^[:space:]]\+\).*/\1/p')

            [ -n "$uses_ref" ] || continue

            case "$uses_ref" in
                ./*|../*|*/.github/workflows/* )
                    continue
                    ;;
            esac

            repo="${uses_ref%@*}"
            ref="${uses_ref#*@}"

            case "$repo" in
                */*) ;;
                *) continue ;;
            esac

            discovered_version=''
            if [ -n "$comment_version" ]; then
                discovered_version="$comment_version"
            elif printf '%s\n' "$ref" | grep -Eq '^v?[0-9]+(\.|$)'; then
                discovered_version="$ref"
            fi

            if ! printf '%s\n' "$ref" | grep -Eq '^[0-9a-f]{40}$'; then
                printf '%s⚠️  Unpinned action reference at %s:%s -> %s%s\n' "$YELLOW" "$workflow" "$line_number" "$uses_ref" "$NC" >&2
                DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
            fi

            if [ -z "$discovered_version" ]; then
                printf '%s⚠️  Cannot determine version for %s at %s:%s (add '\''# vX.Y.Z'\'' comment)%s\n' "$YELLOW" "$repo" "$workflow" "$line_number" "$NC" >&2
                DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
                continue
            fi

            version="$discovered_version"

            # Use composite key to support multiple versions of the same action
            local action_key="${repo}@${version}"
            local existing_ref="${ACTION_REFS[$action_key]}"

            if [ -n "$existing_ref" ] && [ "$existing_ref" != "$ref" ]; then
                printf '%s⚠️  Conflicting pinned refs detected for %s: %s (%s) vs %s (%s:%s)%s\n' "$YELLOW" "$action_key" "$existing_ref" "${ACTION_SOURCES[$action_key]}" "$ref" "$workflow" "$line_number" "$NC" >&2
                DISCOVERY_WARNINGS=$((DISCOVERY_WARNINGS + 1))
                continue
            fi

            ACTIONS["$action_key"]="$version"
            # Append source, separating multiple occurrences with newlines
            if [ -n "${ACTION_SOURCES[$action_key]}" ]; then
                ACTION_SOURCES["$action_key"]="${ACTION_SOURCES[$action_key]}"$'\n'"$workflow:$line_number"
            else
                ACTION_SOURCES["$action_key"]="$workflow:$line_number"
            fi
            ACTION_REFS["$action_key"]="$ref"
        done < "$workflow"
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

        if printf '%s\n' "$ref" | grep -Eq '^[0-9a-f]{40}$'; then
            ref_display="\`${ref:0:8}\`"
        else
            ref_display="\`$ref\`"
        fi

        printf '| %s | %s | %s | `%s` |\n' "$action" "$version" "$ref_display" "$source"
    done < <(printf '%s\n' "${!ACTIONS[@]}" | sort)
}

get_latest_compatible_release() {
    local action="$1"
    local current_version="$2"
    local tags latest repo_path

    # Extract owner/repo (first two parts) for git ls-remote, handles both:
    # - actions/checkout
    # - github/codeql-action/init
    repo_path=$(printf '%s\n' "$action" | cut -d/ -f1-2)

    tags=$($TIMEOUT_BIN 10 $GIT_BIN ls-remote --tags --refs "https://github.com/$repo_path.git" 2>/dev/null | awk '{print $2}' | sed 's#refs/tags/##')

    if [ -z "$tags" ]; then
        printf '\n'
        return
    fi

    # Get the absolute latest version tag (semver-like only, at least X.Y to exclude
    # floating major alias tags like v1, v2 and non-version tags like "verbose")
    latest=$(printf '%s\n' "$tags" | grep -E '^v?[0-9]+\.[0-9]+' | sort -V | tail -n 1)

    printf '%s\n' "$latest"
}

if [ "$MODE" = "check" ]; then
    print_centered_banner "GitHub Actions Update Checker"
fi

discover_actions

if [ "$DISCOVERY_WARNINGS" -gt 0 ] && [ "$MODE" = "check" ]; then
    printf '%s⚠️  Discovery warnings found: %s%s\n' "$YELLOW" "$DISCOVERY_WARNINGS" "$NC"
    printf '\n'
    printf '%sContinuing with version checks so all findings are shown in this run.%s\n' "$YELLOW" "$NC"
    printf '\n'
fi

if [ ${#ACTIONS[@]} -eq 0 ]; then
    printf '%s⚠️  No pinned GitHub Actions discovered from %s%s\n' "$YELLOW" "$WORKFLOW_DIR" "$NC"
    if [ "$DISCOVERY_WARNINGS" -gt 0 ]; then
        exit 2
    fi
    exit 0
fi

if [ "$MODE" = "current-pins-md" ]; then
    print_current_pins_markdown
    if [ "$DISCOVERY_WARNINGS" -gt 0 ]; then
        exit 2
    fi
    exit 0
fi

printf 'Checking %s GitHub Actions for updates...\n' "${#ACTIONS[@]}"
printf '\n'

# Group actions by repo name
declare -A repo_versions
for action_key in "${!ACTIONS[@]}"; do
    repo_name="${action_key%@*}"
    if [ -z "${repo_versions[$repo_name]}" ]; then
        repo_versions["$repo_name"]="$action_key"
    else
        repo_versions["$repo_name"]="${repo_versions[$repo_name]} $action_key"
    fi
done

# Check each action repo (potentially with multiple versions)
for repo_name in $(printf '%s\n' "${!repo_versions[@]}" | sort); do
    action_keys="${repo_versions[$repo_name]}"

    # Track if any version is outdated for this repo
    repo_has_update=0
    unset versions_info
    declare -a versions_info

    for action_key in $action_keys; do
        current_version="${ACTIONS[$action_key]}"
        ((TOTAL++))

        latest=$(get_latest_compatible_release "$repo_name" "$current_version")

        if [ -z "$latest" ] || [ "$latest" = "null" ]; then
            versions_info+=("$current_version||unable||")
            continue
        fi

        latest_normalized="${latest#v}"
        current_normalized="${current_version#v}"

        repo_path=$(printf '%s\n' "$repo_name" | cut -d/ -f1-2)
        sha=$($TIMEOUT_BIN 10 $GIT_BIN ls-remote --tags "https://github.com/$repo_path.git" "refs/tags/$latest" 2>/dev/null | awk '{print $1}')

        if [ -z "$sha" ]; then
            versions_info+=("$current_version||error||")
            continue
        fi

        if [ "$latest_normalized" != "$current_normalized" ]; then
            repo_has_update=1
            # Store with ||| as source delimiter (won't appear in paths)
            sources_str=$(printf '%s\n' "${ACTION_SOURCES[$action_key]}" | tr '\n' '|' | sed 's/|$//')
            versions_info+=("${current_version}	${latest}	${sha}	${sources_str}")
        else
            ((CURRENT++))
            versions_info+=("${current_version}	current	")
        fi
    done

    # Display repo with all versions
    if [ $repo_has_update -eq 1 ]; then
        printf '%s⚠️ UPDATE AVAILABLE: %s%s\n' "$RED" "$repo_name" "$NC"
        for info in "${versions_info[@]}"; do
            # Use printf to handle tab-separated fields
            current=$(printf '%s\n' "$info" | cut -f1)
            latest=$(printf '%s\n' "$info" | cut -f2)
            sha=$(printf '%s\n' "$info" | cut -f3)
            sources_str=$(printf '%s\n' "$info" | cut -f4-)

            if [ "$latest" = "current" ]; then
                printf '   %s (up-to-date)\n' "$current"
            elif [ "$latest" = "unable" ]; then
                printf '   %s -> (unable to fetch)\n' "$current"
            elif [ "$latest" = "error" ]; then
                printf '   %s -> (error fetching SHA)\n' "$current"
            else
                printf '   %s -> %s\n' "$current" "$latest"
                printf '      SHA:   %s\n' "$sha"
                printf '      Short: %s\n' "${sha:0:8}"

                # Display sources for this version (pipe-delimited, convert to newlines)
                sources_array=()
                i=0
                while IFS= read -r line; do
                    [ -n "$line" ] && sources_array[$i]="$line" && ((i++))
                done < <(printf '%s\n' "$sources_str" | tr '|' '\n')

                for idx in "${!sources_array[@]}"; do
                    if [ "$idx" -eq 0 ]; then
                        printf '      Source: %s\n' "${sources_array[$idx]}"
                    else
                        printf '              %s\n' "${sources_array[$idx]}"
                    fi
                done
                ((OUTDATED++))
            fi
        done
        printf '\n'
    else
        # All versions of this repo are up-to-date
        all_current=1
        for info in "${versions_info[@]}"; do
            latest=$(printf '%s\n' "$info" | cut -f2)
            if [ "$latest" != "current" ] && [ -n "$latest" ]; then
                all_current=0
                break
            fi
        done

        if [ $all_current -eq 1 ]; then
            printf '%s✓ %s%s\n' "$GREEN" "$repo_name" "$NC"
            for info in "${versions_info[@]}"; do
                current=$(printf '%s\n' "$info" | cut -f1)
                printf '   %s (up-to-date)\n' "$current"
            done
            printf '\n'
        fi
    fi
done

# Summary
print_centered_banner "Summary"
printf 'Total actions checked:   %s\n' "$TOTAL"
printf 'Discovery warnings:      %s%s%s\n' "$YELLOW" "$DISCOVERY_WARNINGS" "$NC"
printf 'Up-to-date:              %s%s%s\n' "$GREEN" "$CURRENT" "$NC"
printf 'Updates available:       %s%s%s\n' "$RED" "$OUTDATED" "$NC"
printf '\n'

if [ $OUTDATED -gt 0 ]; then
    printf '%sTo update actions:%s\n' "$YELLOW" "$NC"
    printf '1. Review the new versions and their changelogs\n'
    printf '2. Follow the update process in doc/ACTIONS_UPDATE.md\n'
    printf '3. Test changes in a feature branch\n'
    printf '4. Create a PR and verify CI passes\n'
    printf '\n'
fi

if [ "$DISCOVERY_WARNINGS" -gt 0 ]; then
    printf '%sDiscovery checks failed. Fix workflow pinning/version consistency first.%s\n' "$RED" "$NC"
    printf '\n'
    exit 2
fi

if [ $OUTDATED -gt 0 ]; then
    exit 1
fi

printf '%sAll actions are up-to-date!%s\n' "$GREEN" "$NC"
printf '\n'
exit 0
