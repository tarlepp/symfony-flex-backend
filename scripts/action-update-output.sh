#!/usr/bin/env bash

source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"
output_status RUN 'GitHub Action pins and available updates (informational)'
status=0
ACTION_OUTPUT_WRAPPED=1 "$@" || status=$?
printf '\n' >&2
case "$status" in
    0) output_status OK 'Action and tool update checks completed' ;;
    1) output_status WARN 'Updates available (exit 1; informational Make target remains successful)' ;;
    2) output_status FAIL 'Pin discovery or lookup checks failed (exit 2; informational Make target remains successful)' ;;
    130|143)
        output_status WARN "GitHub Actions and workflow tool updates: cancelled (exit $status)"
        exit "$status"
        ;;
    *) output_status FAIL "Action update checker could not complete (exit $status)"; exit "$status" ;;
esac
