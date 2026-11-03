#!/usr/bin/env bash

# Resolve native and status colors separately before redirecting stderr.
if [ -z "${NATIVE_COLOR+x}" ]; then
    if [ -z "${NO_COLOR:-}" ] && {
        [ "${COLOR:-auto}" = always ] ||
        { [ "${COLOR:-auto}" = auto ] && [ -t 1 ] && [ "${TERM:-}" != dumb ]; }
    }; then
        export NATIVE_COLOR=always
    else
        export NATIVE_COLOR=never
    fi
fi

if [ "${COLOR:-auto}" = auto ]; then
    if [ -t 2 ] && [ "${TERM:-}" != dumb ]; then
        export COLOR=always
    else
        export COLOR=never
    fi
fi

filter_make_errors() {
    local line complete
    local pattern='^make(\[[0-9]+\])?: \*\*\* \[.+\] Error [0-9]+$'
    while true; do
        complete=true
        IFS= read -r line || complete=false
        if [[ ! "$line" =~ $pattern ]]; then
            printf '%s' "$line"
            if [ "$complete" = true ]; then
                printf '\n'
            fi
        fi
        [ "$complete" = true ] || break
    done
}

exec {diagnostics}> >(filter_make_errors >&2)
filter_pid=$!
command make "$@" 2>&"$diagnostics"
status=$?
exec {diagnostics}>&-
wait "$filter_pid"
exit "$status"
