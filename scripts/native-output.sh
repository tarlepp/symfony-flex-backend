#!/usr/bin/env bash

source "$(dirname "$0")/command-output.sh"

if [ "$#" -eq 0 ]; then
    output_status FAIL 'Usage: native-output.sh <command> [arguments...]'
    exit 64
fi

if [ -n "$OUTPUT_RESET" ]; then
    exec "$@"
fi

# Only SGR decoration is removed; progress controls and native streams remain.
strip_sgr() {
    sed -u $'s/\033\\[[0-9;]*m//g'
}

exec {stdout}> >(strip_sgr)
stdout_pid=$!
exec {stderr}> >(strip_sgr >&2)
stderr_pid=$!
"$@" >&"$stdout" 2>&"$stderr"
status=$?
exec {stdout}>&-
exec {stderr}>&-
wait "$stdout_pid"
stdout_status=$?
wait "$stderr_pid"
stderr_status=$?
if [ "$status" -ne 0 ]; then
    exit "$status"
fi
if [ "$stdout_status" -ne 0 ] || [ "$stderr_status" -ne 0 ]; then
    output_status FAIL 'Unable to filter native color output'
    exit 1
fi
