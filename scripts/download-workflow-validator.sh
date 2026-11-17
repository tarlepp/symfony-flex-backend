#!/usr/bin/env bash
set -euo pipefail

url=$1
checksum=$2
file=$3
cache=$4

if [[ -f "$cache" ]]; then
    printf '%s  %s\n' "$checksum" "$cache" | sha256sum --check --quiet
    cp "$cache" "$file"
    exit 0
fi

curl --fail --silent --show-error --location \
    --connect-timeout 10 --max-time 60 --retry 3 --retry-max-time 120 \
    "$url" --output "$file"
printf '%s  %s\n' "$checksum" "$file" | sha256sum --check --quiet
mkdir -p "$(dirname "$cache")"
staging=$(mktemp "${cache}.XXXXXX")
if cp "$file" "$staging" && mv "$staging" "$cache"; then
    exit 0
else
    status=$?
    rm -f "$staging"
    exit "$status"
fi
