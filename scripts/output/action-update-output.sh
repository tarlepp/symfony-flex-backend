#!/usr/bin/env bash

#
# Decorate output from the GitHub Action and workflow-tool update checker.
#
# Usage:
#   Run with the checker command and its arguments.
#
# Details:
#   Update and lookup findings are informational; cancellation and unexpected
#   failure statuses are preserved.
#
# Callers:
#   Makefile:
#     - `check-action-updates` target.
#

source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"

output_status RUN 'GitHub Action pins and available updates (informational)'

status=0
ACTION_OUTPUT_WRAPPED=1 "$@" || status=$?

case "$status" in
    0)
        output_outcome OK 'Action and tool update checks completed'
        ;;
    1)
        output_outcome WARN \
            'Updates available (exit 1; informational Make target remains successful)'
        ;;
    2)
        output_outcome FAIL \
            'Action/tool checks incomplete: review the failed entries above; resolve pin inconsistencies or retry failed GitHub lookups (checker exit 2; informational Make target remains successful)'
        ;;
    130|143)
        output_outcome WARN "GitHub Actions and workflow tool updates: cancelled (exit $status)"
        exit "$status"
        ;;
    *)
        output_outcome FAIL "Action update checker could not complete (exit $status)"
        exit "$status"
        ;;
esac
