#!/usr/bin/env bash
set -euo pipefail

if [ -z "${COMPOSER_CACHE_DIR:-}" ]; then
    echo "COMPOSER_CACHE_DIR must identify the mounted container cache." >&2
    exit 1
fi

cache=$(composer config cache-dir)
if [ "$cache" != "$COMPOSER_CACHE_DIR" ]; then
    echo "Composer cache directory does not match the mounted container cache." >&2
    exit 1
fi

mkdir -p "$cache"
if [ ! -w "$cache" ]; then
    echo "Composer cache directory is not writable by the container user." >&2
    exit 1
fi

printf 'Composer download cache is mounted and writable: %s\n' "$cache"
