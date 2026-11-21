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
#     - `scripts/make/bashrc.sh` sources the interactive shell integration.
#   Docker:
#     - `docker/bash/make.sh` sources it when the repository mount is present.
#

SYMFONY_MAKE_LAUNCHER="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/make.sh"
SYMFONY_MAKE_ROOT="${SYMFONY_MAKE_LAUNCHER%/scripts/make/make.sh}"
SYMFONY_MAKE_HELP="${SYMFONY_MAKE_LAUNCHER%/*}/make-help.sh"

make() {
    if [ "$PWD" = "$SYMFONY_MAKE_ROOT" ]; then
        command bash "$SYMFONY_MAKE_LAUNCHER" "$@"
    else
        command make "$@"
    fi
}

_symfony_make_completion() {
    COMPREPLY=()
    [[ $PWD == "$SYMFONY_MAKE_ROOT" && $COMP_CWORD -gt 0 ]] || return 0

    local current="${COMP_WORDS[COMP_CWORD]}" records target
    [[ $current != -* && $current != *=* ]] || return 0
    records="$(bash "$SYMFONY_MAKE_HELP" --targets)" || return

    while IFS=$'\t' read -r target _; do
        if [[ -n $target && $target == "$current"* ]]; then
            COMPREPLY+=("$target")
        fi
    done <<< "$records"
}

complete -o default -F _symfony_make_completion make

make-pick() {
    if [[ $PWD != "$SYMFONY_MAKE_ROOT" ]]; then
        printf 'make-pick is available only from the repository root\n' >&2
        return 2
    fi

    if ! command -v fzf >/dev/null 2>&1; then
        printf 'fzf is required for make-pick\n' >&2
        return 127
    fi

    local records selected target
    records="$(bash "$SYMFONY_MAKE_HELP" --targets)" || return
    selected="$(fzf --no-multi --prompt='make> ' --header='Enter runs the selected target' \
        --height=80% <<< "$records")" || return
    target="${selected%%$'\t'*}"
    [[ $target =~ ^[a-zA-Z0-9][a-zA-Z0-9-]*$ && $selected == "$target"$'\t'* ]] || return 1

    make "$target"
}
