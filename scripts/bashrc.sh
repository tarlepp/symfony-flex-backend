#!/usr/bin/env bash

#
# Configure interactive Bash shells in the development container.
#
# Usage:
#   Source as the interactive shell's startup file.
#
# Details:
#   Loads the user's Bash configuration first, then installs the repository
#   Make wrapper for commands run from the project root.
#

if [ -f "$HOME/.bashrc" ]; then
    source "$HOME/.bashrc"
fi

source "$(dirname "${BASH_SOURCE[0]}")/make-shell.sh"
