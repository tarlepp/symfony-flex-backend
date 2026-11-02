#!/usr/bin/env bash

source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"

decorate_diagnostics() {
    local line complete errors_started=false
    local pattern='^.+:[0-9]+(:[0-9]+)? error MD[0-9]+/'
    while true; do
        complete=true
        IFS= read -r line || complete=false
        if [[ "$line" =~ $pattern ]]; then
            if [ "$errors_started" = false ]; then
                printf '\n'
                errors_started=true
            fi
            printf '%s[ERROR]%s ' "$OUTPUT_RED" "$OUTPUT_RESET"
        fi
        printf '%s' "$line"
        if [ "$complete" = true ]; then
            printf '\n'
        else
            break
        fi
    done
}

exec {output}> >(decorate_diagnostics)
output_pid=$!
exec {diagnostics}> >(decorate_diagnostics >&2)
diagnostics_pid=$!
"$@" >&"$output" 2>&"$diagnostics"
status=$?
exec {output}>&-
exec {diagnostics}>&-
wait "$output_pid"
wait "$diagnostics_pid"
exit "$status"
