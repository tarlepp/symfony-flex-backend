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
# Callers:
#   Scripts:
#     - `scripts/bashrc.sh` sources the interactive shell integration.
#   Docker:
#     - `docker/bash/make.sh` sources it when the repository mount is present.
#
SYMFONY_MAKE_LAUNCHER="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/make.sh"

make() {
    if [ "$PWD" = "${SYMFONY_MAKE_LAUNCHER%/scripts/make.sh}" ]; then
        command bash "$SYMFONY_MAKE_LAUNCHER" "$@"
    else
        command make "$@"
    fi
}
