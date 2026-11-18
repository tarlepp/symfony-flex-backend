#!/usr/bin/env bash

# Source this Bash helper to route project-root Make commands through the
# repository launcher, which filters Make's recipe-error diagnostics.
# Outside the repository root, the user's normal Make command remains unchanged.
SYMFONY_MAKE_LAUNCHER="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/make.sh"

make() {
    if [ "$PWD" = "${SYMFONY_MAKE_LAUNCHER%/scripts/make.sh}" ]; then
        command bash "$SYMFONY_MAKE_LAUNCHER" "$@"
    else
        command make "$@"
    fi
}
