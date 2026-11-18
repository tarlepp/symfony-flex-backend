#!/usr/bin/env bash

#
# Verify the Composer cache mount from the CI PHP container.
#
# Usage:
#   Run with COMPOSER_CACHE_DIR set to the mounted cache path.
#
# Details:
#   The path must match Composer's configured cache directory and be writable
#   by the container user; the directory is created when necessary.
#
# Callers:
#   GitHub Actions:
#     - `.github/actions/php-bootstrap/action.yml` runs this in the CI PHP service.
#

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
