#!/usr/bin/env bash

#
# Render a reusable GitHub Actions job-summary overview.
#
# Usage:
#   FOOTER CHECK RESULT [CHECK RESULT ...], with EVENT_NAME and PRIOR_STATUS set
#   in the environment.
#
# Details:
#   Optional TARGET and OVERVIEW_TITLE customize the heading. Set
#   OVERVIEW_COLLAPSED=true for expandable job details, opened on attention.
#   PRIOR_STATUS is the job status before the overview, not each step outcome.
#   Writes Markdown to stdout for the calling workflow step to append.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/main.yml`
#     - `.github/workflows/action-updates.yml`
#     - `.github/workflows/dependency-review.yml`
#     - `.github/workflows/docker-image-updates.yml`
#     - `.github/workflows/scorecard.yml`
#     - `.github/workflows/codeql.yml`
#     - `.github/workflows/vulnerability-scan.yml`
#

set -euo pipefail

if (( $# < 3 || $# % 2 == 0 )); then
    echo "Usage: $0 FOOTER CHECK RESULT [CHECK RESULT ...]" >&2
    exit 2
fi

footer=$1
shift

: "${EVENT_NAME:?EVENT_NAME is required}"
: "${PRIOR_STATUS:?PRIOR_STATUS is required}"

overview="✅ Reported checks successful before overview"
attention=false
incomplete=false
if [ "$PRIOR_STATUS" != success ]; then
    attention=true
fi

overview_args=("$@")
for ((index = 0; index < ${#overview_args[@]}; index += 2)); do
    label=${overview_args[index]}
    result=${overview_args[index + 1]}
    case "$label" in
        Analyzer)
            case "$result" in
                Psalm|PHPStan|"PHP Insights") ;;
                *) attention=true ;;
            esac
            ;;
        "Update finding")
            [ "$result" = current ] || attention=true
            ;;
        "Coveralls token"|"Sonar token"|"PR SARIF eligibility (same-repository PRs only)")
            case "$result" in
                available|"not available"|eligible|"not eligible") incomplete=true ;;
                *) attention=true ;;
            esac
            ;;
        *)
            case "$result" in
                success) ;;
                skipped) incomplete=true ;;
                *) attention=true ;;
            esac
            ;;
    esac
done

if [ "$attention" = true ]; then
    overview="⚠️ Reported results need attention before overview"
elif [ "$incomplete" = true ]; then
    overview="➖ Skipped or informational results before overview"
fi

if [ "${OVERVIEW_COLLAPSED:-false}" = true ]; then
    if [ "$attention" = true ]; then
        echo '<details open>'
    else
        echo '<details>'
    fi
    printf '<summary>%s' "${OVERVIEW_TITLE:-Workflow run overview}"
    if [ -n "${TARGET:-}" ]; then
        printf ' · %s' "$TARGET"
    fi
    printf ' · %s</summary>\n' "$overview"
else
    printf '## %s' "${OVERVIEW_TITLE:-Workflow run overview}"
    if [ -n "${TARGET:-}" ]; then
        printf ' · %s' "$TARGET"
    fi
    printf '\n\n%s\n' "$overview"
fi
echo
printf 'Event: %s' "$EVENT_NAME"
if [ -n "${TARGET:-}" ]; then
    printf ' · Image: %s' "$TARGET"
fi
printf ' · Job status before overview: **%s**\n\n' "$PRIOR_STATUS"
echo '| Check | Result |'
echo '| --- | --- |'

while (( $# )); do
    printf '| %s | %s |\n' "$1" "$2"
    shift 2
done

echo
printf '%s\n' "$footer"
if [ "${OVERVIEW_COLLAPSED:-false}" = true ]; then
    printf '\n</details>\n'
fi
