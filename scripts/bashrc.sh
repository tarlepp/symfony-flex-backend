#!/usr/bin/env bash

# Bash startup file for interactive shells in the development container.
# Load the user's normal configuration first, then install the repository's
# Make wrapper so it takes precedence for commands run from the project root.

if [ -f "$HOME/.bashrc" ]; then
    source "$HOME/.bashrc"
fi

source "$(dirname "${BASH_SOURCE[0]}")/make-shell.sh"
