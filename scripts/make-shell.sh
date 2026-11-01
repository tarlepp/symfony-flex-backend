#!/usr/bin/env bash

# Source this file in Bash to enable cleaner Make output for this repository.
SYMFONY_MAKE_LAUNCHER="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/make.sh"

make() {
    if [ "$PWD" = "${SYMFONY_MAKE_LAUNCHER%/scripts/make.sh}" ]; then
        command bash "$SYMFONY_MAKE_LAUNCHER" "$@"
    else
        command make "$@"
    fi
}
