#!/usr/bin/env bash

#
# Render a reusable GitHub Actions job-summary overview.
#
# Usage:
#   FOOTER CHECK RESULT [CHECK RESULT ...], with EVENT_NAME and PRIOR_STATUS set
#   in the environment.
#
# Details:
#   Optional TARGET and OVERVIEW_TITLE customize the heading. Writes Markdown to
#   stdout for the calling workflow step to append to its summary.
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

printf '## %s\n' "${OVERVIEW_TITLE:-Workflow run overview}"
echo
printf 'Event: %s' "$EVENT_NAME"
if [ -n "${TARGET:-}" ]; then
    printf ' · Image: %s' "$TARGET"
fi
printf ' · Prior steps: **%s**\n\n' "$PRIOR_STATUS"
echo '| Check | Result |'
echo '| --- | --- |'

while (( $# )); do
    printf '| %s | %s |\n' "$1" "$2"
    shift 2
done

echo
printf '%s\n' "$footer"
