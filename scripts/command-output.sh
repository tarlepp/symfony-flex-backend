#!/usr/bin/env bash

OUTPUT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)

output_init() {
    OUTPUT_RESET=''
    OUTPUT_BLUE=''
    OUTPUT_GREEN=''
    OUTPUT_YELLOW=''
    OUTPUT_RED=''
    if [ -z "${NO_COLOR:-}" ] && {
        [ "${COLOR:-auto}" = always ] ||
        { [ "${COLOR:-auto}" = auto ] && [ -t 2 ] && [ "${TERM:-}" != dumb ]; }
    }; then
        OUTPUT_RESET=$'\033[0m'
        OUTPUT_BLUE=$'\033[34m'
        OUTPUT_GREEN=$'\033[32m'
        OUTPUT_YELLOW=$'\033[33m'
        OUTPUT_RED=$'\033[31m'
    fi
    OUTPUT_PROGRESS=0
    if [ "${PROGRESS:-auto}" = always ] || {
        [ "${PROGRESS:-auto}" = auto ] && [ -t 2 ] && [ "${TERM:-}" != dumb ] &&
        [ -z "${CI:-}" ] && [ -z "${NO_COLOR:-}" ]
    }; then
        OUTPUT_PROGRESS=1
    fi
}

output_clock() {
    if [ -n "${EPOCHREALTIME:-}" ]; then
        printf '%s' "${EPOCHREALTIME//[.,]/}"
    else
        printf '%s' "$((SECONDS * 1000000))"
    fi
}

output_duration() {
    local tenths seconds minutes hours
    tenths=$((($1 + 50000) / 100000))
    if [ "$tenths" -lt 600 ]; then
        printf '%d.%ds' "$((tenths / 10))" "$((tenths % 10))"
        return
    fi
    seconds=$((($1 + 500000) / 1000000))
    hours=$((seconds / 3600))
    minutes=$((seconds % 3600 / 60))
    seconds=$((seconds % 60))
    if [ "$hours" -gt 0 ]; then
        printf '%dh %02dm %02ds' "$hours" "$minutes" "$seconds"
    else
        printf '%dm %02ds' "$minutes" "$seconds"
    fi
}

output_status() {
    local status=$1 message=$2 color=''
    case "$status" in
        RUN|INFO) color=$OUTPUT_BLUE ;;
        OK) color=$OUTPUT_GREEN ;;
        WARN|SKIP) color=$OUTPUT_YELLOW ;;
        FAIL) color=$OUTPUT_RED ;;
    esac
    printf '%s[%s]%s %s\n' "$color" "$status" "$OUTPUT_RESET" "$message" >&2
}

output_result() {
    printf '\n' >&2
    output_status "$@"
}

output_run() {
    local label=$1 status=0 started='' took=''
    shift
    output_status RUN "$label"
    if [ "$OUTPUT_PROGRESS" = 1 ]; then
        started=$(output_clock)
    fi
    trap 'took=$(output_elapsed "$started"); output_result WARN "$label: interrupted${took:+ (took $took)}"; exit 130' INT
    trap 'took=$(output_elapsed "$started"); output_result WARN "$label: terminated${took:+ (took $took)}"; exit 143' TERM
    if [ "$OUTPUT_PROGRESS" = 1 ]; then
        OUTPUT_PROGRESS_STARTED='' output_relay "$label" "$@" || status=$?
    else
        OUTPUT_PROGRESS_STARTED='' "$@" || status=$?
    fi
    trap - INT TERM
    took=$(output_elapsed "$started")
    if [ "$status" -eq 0 ]; then
        output_result "${OUTPUT_SUCCESS_STATUS:-OK}" "${OUTPUT_SUCCESS_MESSAGE:-$label}${took:+ (took $took)}"
    elif [ "$status" -eq 130 ] || [ "$status" -eq 143 ]; then
        output_result WARN "$label: cancelled (exit $status${took:+, took $took})"
    else
        output_result FAIL "$label (exit $status${took:+, took $took})"
    fi
    return "$status"
}

# Run a command with the idle spinner; nested commands receive PROGRESS=never.
output_relay() {
    local label=$1 style=plain
    shift
    if [ "$(type -t "$1")" = file ] && command -v python3 >/dev/null 2>&1; then
        [ -z "$OUTPUT_BLUE" ] || style=color
        # Disable Python locale coercion so the command inherits the caller's locale unchanged.
        OUTPUT_PROGRESS_COERCE=${PYTHONCOERCECLOCALE+set:$PYTHONCOERCECLOCALE} PYTHONCOERCECLOCALE=0 \
            python3 "$OUTPUT_DIR/command-progress.py" "$label" "$style" "$@"
    else
        PROGRESS=never "$@"
    fi
}

# Relay a command that reports its own RUN and outcome lines; it ends them with output_outcome.
output_progress() {
    local label=$1
    shift
    if [ "$OUTPUT_PROGRESS" = 1 ]; then
        OUTPUT_PROGRESS_STARTED=$(output_clock)
        export OUTPUT_PROGRESS_STARTED
        output_relay "$label" "$@"
    else
        unset OUTPUT_PROGRESS_STARTED
        "$@"
    fi
}

# Final outcome of a command run via output_progress, including its elapsed time.
output_outcome() {
    local took
    took=$(output_elapsed "${OUTPUT_PROGRESS_STARTED:-}")
    output_result "$1" "$2${took:+ (took $took)}"
}

# Print the elapsed time since a start clock, or nothing when progress is disabled.
output_elapsed() {
    [ -z "$1" ] || output_duration "$(($(output_clock) - $1))"
}

output_stream() {
    local label=$1
    shift
    output_status RUN "$label"
    exec "$@"
}

output_init

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    if [ "${1:-}" = --status ]; then
        if [ "$#" -ne 3 ] || [[ ! "$2" =~ ^(RUN|OK|WARN|SKIP|FAIL|INFO)$ ]]; then
            output_status FAIL 'Usage: command-output.sh --status <RUN|OK|WARN|SKIP|FAIL|INFO> <message>'
            exit 64
        fi
        output_status "$2" "$3"
        exit 0
    fi
    mode=run
    case "${1:-}" in
        --run|--stream|--progress)
            mode=${1#--}
            shift
            ;;
    esac
    if [ "$#" -lt 2 ]; then
        output_status FAIL 'Usage: command-output.sh [--run|--stream|--progress] <label> <command> [arguments...]'
        exit 64
    fi
    if [ "$mode" = stream ]; then
        output_stream "$@"
    elif [ "$mode" = progress ]; then
        output_progress "$@"
    else
        output_run "$@"
    fi
fi
