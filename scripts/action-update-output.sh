#!/usr/bin/env bash

source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"
output_status RUN 'GitHub Action pins and available updates (informational)'
status=0
ACTION_OUTPUT_WRAPPED=1 "$@" || status=$?
case "$status" in
    0) output_outcome OK 'Action and tool update checks completed' ;;
    1) output_outcome WARN 'Updates available (exit 1; informational Make target remains successful)' ;;
    2) output_outcome FAIL 'Pin discovery or lookup checks failed (exit 2; informational Make target remains successful)' ;;
    130|143)
        output_outcome WARN "GitHub Actions and workflow tool updates: cancelled (exit $status)"
        exit "$status"
        ;;
    *) output_outcome FAIL "Action update checker could not complete (exit $status)"; exit "$status" ;;
esac
