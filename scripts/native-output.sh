#!/usr/bin/env bash

# Run a native command with the repository's color policy.
# Pass the command and its arguments; when native color is disabled, filter
# SGR escape sequences from the live output without changing the command status.

script_dir=${BASH_SOURCE[0]%/*}
source "$script_dir/command-output.sh"

if [ "$#" -eq 0 ]; then
    output_status FAIL 'Usage: native-output.sh <command> [arguments...]'
    exit 64
fi

if [ -z "${NO_COLOR:-}" ] && {
    [ "${NATIVE_COLOR:-}" = always ] ||
    {
        [ -z "${NATIVE_COLOR:-}" ] && {
            [ "${COLOR:-auto}" = always ] ||
            {
                [ "${COLOR:-auto}" = auto ] &&
                [ -t 1 ] &&
                [ "${TERM:-}" != dumb ]
            }
        }
    }
}; then
    exec "$@"
fi

exec python3 "$script_dir/native-output.py" "$@"
