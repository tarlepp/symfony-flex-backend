#!/usr/bin/env bash

#
# Download or reuse a SHA-256-pinned workflow validator.
#
# Usage:
#   <url> <expected-sha256> <destination> <cache-file>.
#
# Details:
#   Network downloads use bounded retries; cached and downloaded files are
#   checksum-verified before use.
#
# Callers:
#   Scripts:
#     - `scripts/lint-workflows.sh` downloads and verifies pinned validators.
#

set -euo pipefail

url=$1
checksum=$2
destination=$3
cache_file=$4

if [[ -f "$cache_file" ]]; then
    printf '%s  %s\n' "$checksum" "$cache_file" | sha256sum --check --quiet
    cp "$cache_file" "$destination"
    exit 0
fi

curl --fail --silent --show-error --location \
    --connect-timeout 10 --max-time 60 --retry 3 --retry-max-time 120 \
    "$url" --output "$destination"

printf '%s  %s\n' "$checksum" "$destination" | sha256sum --check --quiet

mkdir -p "$(dirname "$cache_file")"
staging=$(mktemp "${cache_file}.XXXXXX")

if cp "$destination" "$staging" && mv "$staging" "$cache_file"; then
    exit 0
else
    status=$?
    rm -f "$staging"
    exit "$status"
fi
