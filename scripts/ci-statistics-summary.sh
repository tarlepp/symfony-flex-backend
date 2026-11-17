#!/usr/bin/env bash
set -euo pipefail

if (( $# < 3 )); then
    echo "Usage: $0 run TITLE COMMAND [ARG ...] | {file|versions|insights} TITLE PATH" >&2
    exit 2
fi

mode=$1
title=$2
shift 2
: "${GITHUB_STEP_SUMMARY:?GITHUB_STEP_SUMMARY is required}"

status=0
case "$mode" in
    run)
        output=$(mktemp)
        trap 'rm -f "$output"' EXIT
        if COLOR=never NATIVE_COLOR=never NO_COLOR=1 "$@" | tee "$output"; then
            :
        else
            status=$?
        fi
        ;;
    file|versions|insights)
        if (( $# != 1 )) || [ ! -f "$1" ]; then
            echo "Statistics output file is missing or invalid." >&2
            exit 2
        fi
        output=$1
        if [ "$mode" != file ]; then
            filtered=$(mktemp)
            trap 'rm -f "$filtered"' EXIT
            if [ "$mode" = versions ]; then
                awk '
                    /^  PHP [0-9].*\(cli\)/ { sub(/^ +/, ""); print; php++ }
                    /^  Symfony: / { sub(/^ +/, ""); print; symfony++ }
                    /^    Composer version / { sub(/^ +/, ""); print; composer++ }
                    /^      phpunit\/phpunit: / { sub(/^ +/, ""); print; phpunit++ }
                    END { if (php != 1 || symfony != 1 || composer != 1 || phpunit != 1) exit 1 }
                ' "$output" > "$filtered" || {
                    echo "Could not identify all selected tool versions." >&2
                    exit 1
                }
            else
                awk '
                    /^\[(CODE|COMPLEXITY|ARCHITECTURE|MISC)\] / { print; count[$1]++ }
                    END {
                        if (count["[CODE]"] != 1 || count["[COMPLEXITY]"] != 1 ||
                            count["[ARCHITECTURE]"] != 1 || count["[MISC]"] != 1) exit 1
                    }
                ' "$output" > "$filtered" || {
                    echo "Could not identify all PHP Insights scores." >&2
                    exit 1
                }
            fi
            output=$filtered
        fi
        ;;
    *)
        echo "Unknown statistics summary mode: $mode" >&2
        exit 2
        ;;
esac

{
    printf '## %s\n\n' "$title"
    if (( status != 0 )); then
        printf '**Command failed (exit %s); output may be incomplete.**\n\n' "$status"
    fi
    sed 's/^/    /' "$output"
    printf '\n\n'
} >> "$GITHUB_STEP_SUMMARY"
exit "$status"
