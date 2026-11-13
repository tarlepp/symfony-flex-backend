#!/usr/bin/env bash

expect_selected() {
    local selected=$1 result=$2

    case "$selected" in
        true) test "$result" = "success" ;;
        false) test "$result" = "skipped" ;;
        *)
            echo "Invalid change detection output: $selected"
            return 1
            ;;
    esac
}
