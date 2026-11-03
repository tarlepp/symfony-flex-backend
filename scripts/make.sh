#!/usr/bin/env bash

# Resolve native and status colors, and progress, before stderr redirection.
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
if [ "${PROGRESS:-auto}" = auto ]; then
    if [ -t 2 ] && [ "${TERM:-}" != dumb ] && [ -z "${CI:-}" ] && [ -z "${NO_COLOR:-}" ]; then
        export PROGRESS=always
    else
        export PROGRESS=never
    fi
fi
# Record whether stdout shares stderr's terminal; forwarded launchers keep the outer value.
if [ -z "${OUTPUT_PROGRESS_SHARED:-}" ]; then
    if [ -t 1 ] && [ -t 2 ]; then
        export OUTPUT_PROGRESS_SHARED=1
    else
        export OUTPUT_PROGRESS_SHARED=0
    fi
fi

# Partial lines (prompts, progress frames) are forwarded after a short pause
# unless they could still become a Make recipe error line.
filter_make_errors() {
    local chunk status pending='' midline=false
    local pattern='^make(\[[0-9]+\])?: \*\*\* \[.+\] Error [0-9]+$'
    local tail='(:( (\*(\*(\*( (\[.*)?)?)?)?)?)?)?'
    local prefix="^m(a(k(e(${tail}|\\[([0-9]+(\\]${tail})?)?)?)?)?)?$"
    while true; do
        chunk=''
        IFS= read -r -t 0.05 chunk
        status=$?
        pending+="$chunk"
        if [ "$status" -eq 0 ]; then
            if [ "$midline" = true ] || [[ ! "$pending" =~ $pattern ]]; then
                printf '%s\n' "$pending"
            fi
            pending=''
            midline=false
        elif [ "$status" -gt 128 ]; then
            if [ -n "$pending" ] && { [ "$midline" = true ] || [[ ! "$pending" =~ $prefix ]]; }; then
                printf '%s' "$pending"
                pending=''
                midline=true
            fi
        else
            if [ "$midline" = true ] || [[ ! "$pending" =~ $pattern ]]; then
                printf '%s' "$pending"
            fi
            break
        fi
    done
}

exec {diagnostics}> >(filter_make_errors >&2)
filter_pid=$!
command make "$@" 2>&"$diagnostics"
status=$?
exec {diagnostics}>&-
wait "$filter_pid"
exit "$status"
