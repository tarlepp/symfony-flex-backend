#!/usr/bin/env bash

#
# Route project-root Make commands through the repository launcher.
#
# Usage:
#   Source this Bash helper in an interactive shell.
#
# Details:
#   The launcher filters Make recipe-error diagnostics; outside the repository
#   root, the normal Make command remains unchanged.
#
SYMFONY_MAKE_LAUNCHER="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/make.sh"

make() {
    if [ "$PWD" = "${SYMFONY_MAKE_LAUNCHER%/scripts/make.sh}" ]; then
        command bash "$SYMFONY_MAKE_LAUNCHER" "$@"
    else
        command make "$@"
    fi
}
