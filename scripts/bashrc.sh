#!/usr/bin/env bash

if [ -f "$HOME/.bashrc" ]; then
    source "$HOME/.bashrc"
fi

source "$(dirname "${BASH_SOURCE[0]}")/make-shell.sh"
