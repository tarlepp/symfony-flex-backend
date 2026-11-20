#
# Load repository Make tooling and the interactive Bash line editor.
#
# Usage:
#   Source from the container's Bash startup configuration.
#
# Details:
#   The repository mount may be absent, so the wrapper is loaded only when its
#   file exists.
#
# Callers:
#   Docker:
#     - `Dockerfile` adds this file to the development user's `.bashrc`.
#

if [ -f /app/scripts/make/make-shell.sh ]; then
    source /app/scripts/make/make-shell.sh
fi

if [[ $- == *i* && -t 0 && -t 1 && ${TERM:-dumb} != dumb &&
    ${SYMFONY_BASH_LIVE_COMPLETION:-1} != 0 && -z ${BLE_VERSION:-} &&
    -r /usr/local/share/blesh/ble.sh ]]; then
    # Without a user cache, ble.sh falls back to its read-only install directory.
    if [[ -z ${USER:-} ]]; then
        USER=$(id -un)
        export USER
    fi

    if mkdir -p -- "${XDG_CACHE_HOME:-$HOME/.cache}" &&
        source /usr/local/share/blesh/ble.sh --noattach; then
        bleopt complete_auto_menu=250
        ble-import contrib/integration/fzf-completion
        ble-import contrib/integration/fzf-key-bindings
        ble-attach
    fi
fi
