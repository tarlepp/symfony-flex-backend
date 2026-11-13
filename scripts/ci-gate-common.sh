#!/usr/bin/env bash

gate_error() {
    local message=$1

    message=${message//'%'/'%25'}
    message=${message//$'\r'/'%0D'}
    message=${message//$'\n'/'%0A'}
    printf '::error::%s\n' "$message" >&2
}

expect_result() {
    local check=$1 expected=$2 actual=$3

    if [ "$actual" != "$expected" ]; then
        gate_error "$check: expected result '$expected', got '$actual'."
        return 1
    fi
}

expect_selected() {
    local check=$1 selected=$2 result=$3

    case "$selected" in
        true) expect_result "$check" success "$result" ;;
        false) expect_result "$check" skipped "$result" ;;
        *)
            gate_error "$check: expected selection 'true' or 'false', got '$selected' (job result '$result')."
            return 1
            ;;
    esac
}
