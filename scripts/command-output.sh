#!/usr/bin/env bash

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
    local label=$1 status=0
    shift
    output_status RUN "$label"
    trap 'output_result WARN "$label: interrupted"; exit 130' INT
    trap 'output_result WARN "$label: terminated"; exit 143' TERM
    "$@" || status=$?
    trap - INT TERM
    if [ "$status" -eq 0 ]; then
        output_result "${OUTPUT_SUCCESS_STATUS:-OK}" "${OUTPUT_SUCCESS_MESSAGE:-$label}"
    elif [ "$status" -eq 130 ] || [ "$status" -eq 143 ]; then
        output_result WARN "$label: cancelled (exit $status)"
    else
        output_result FAIL "$label (exit $status)"
    fi
    return "$status"
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
        --run|--stream)
            mode=${1#--}
            shift
            ;;
    esac
    if [ "$#" -lt 2 ]; then
        output_status FAIL 'Usage: command-output.sh [--run|--stream] <label> <command> [arguments...]'
        exit 64
    fi
    if [ "$mode" = stream ]; then
        output_stream "$@"
    else
        output_run "$@"
    fi
fi
