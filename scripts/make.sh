#!/usr/bin/env bash

#
# Launch Make with repository-specific output handling.
#
# Usage:
#   Run from the repository root with the same arguments as make.
#
# Details:
#   Resolves output preferences, filters recipe-error diagnostics, and returns
#   Make's exit status.
#

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

    # Match complete recipe error lines, including recursive make[depth] prefixes.
    local complete_error_pattern='^make(\[[0-9]+\])?: \*\*\* \[.+\] Error [0-9]+$'

    # A timed-out partial read stays buffered while it can still become such a line.
    # The tail covers the optional ": *** [target]..." after make or make[depth].
    local partial_error_tail='(:( (\*(\*(\*( (\[.*)?)?)?)?)?)?)?'
    local partial_error_prefix="^m(a(k(e(${partial_error_tail}|\\[([0-9]+(\\]${partial_error_tail})?)?)?)?)?)?$"

    while true; do
        chunk=''
        IFS= read -r -t 0.05 chunk
        status=$?
        pending+="$chunk"

        if [ "$status" -eq 0 ]; then
            if [ "$midline" = true ] || [[ ! "$pending" =~ $complete_error_pattern ]]; then
                printf '%s\n' "$pending"
            fi

            pending=''
            midline=false
        elif [ "$status" -gt 128 ]; then
            if [ -n "$pending" ] && {
                [ "$midline" = true ] || [[ ! "$pending" =~ $partial_error_prefix ]]
            }; then
                printf '%s' "$pending"
                pending=''
                midline=true
            fi
        else
            if [ "$midline" = true ] || [[ ! "$pending" =~ $complete_error_pattern ]]; then
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

# Drain the diagnostic filter without replacing Make's exit status.
exec {diagnostics}>&-
wait "$filter_pid"

exit "$status"
