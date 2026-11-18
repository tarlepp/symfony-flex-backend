#!/usr/bin/env bash

# Run a Markdown linter while marking its file/line diagnostics as failures.
# Invoke with the linter command and arguments; stdout and stderr stay separate,
# and the wrapped command's exit status is returned unchanged.

source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"

decorate_diagnostics() {
    local line complete errors_started=false

    # Match markdownlint file:line[:column] diagnostics, not ordinary output.
    local pattern='^.+:[0-9]+(:[0-9]+)? error MD[0-9]+/'

    while true; do
        complete=true
        IFS= read -r line || complete=false

        if [[ "$line" =~ $pattern ]]; then
            if [ "$errors_started" = false ]; then
                printf '\n'
                errors_started=true
            fi

            printf '%s[FAIL]%s ' "$OUTPUT_RED" "$OUTPUT_RESET"
        fi

        printf '%s' "$line"
        if [ "$complete" = true ]; then
            printf '\n'
        else
            break
        fi
    done
}

# Each stream has its own decorator and first-error separator; neither is merged.
exec {output}> >(decorate_diagnostics)
output_pid=$!
exec {diagnostics}> >(decorate_diagnostics >&2)
diagnostics_pid=$!

"$@" >&"$output" 2>&"$diagnostics"
status=$?

# Close both streams and drain their decorators before returning the command status.
exec {output}>&-
exec {diagnostics}>&-
wait "$output_pid"
wait "$diagnostics_pid"

exit "$status"
