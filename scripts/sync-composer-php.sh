#!/usr/bin/env bash
# Synchronize Composer after a PHP patch update. Run from the repository root
# in the maintenance container, with the original Dockerfile on stdin.
set -euo pipefail

php_version() {
    sed -nE 's/^FROM php:([0-9]+\.[0-9]+\.[0-9]+)-[^[:space:]@]+@sha256:[a-f0-9]{64} AS php-upstream$/\1/p' "$@"
}

old_version=$(php_version)
new_version=$(php_version Dockerfile)
version_pattern='^[0-9]+\.[0-9]+\.[0-9]+$'

if ! [[ "$old_version" =~ $version_pattern && "$new_version" =~ $version_pattern ]]; then
    echo '::error::Expected exactly one pinned PHP patch version in each Dockerfile.' >&2
    exit 1
fi

if [[ "$old_version" == "$new_version" ]]; then
    echo "PHP tag unchanged ($new_version); no Composer refresh needed."
    exit 0
fi

if [[ "${old_version%.*}" != "${new_version%.*}" ]]; then
    echo '::error::PHP minor and major upgrades require manual review.' >&2
    exit 1
fi

shopt -s nullglob
manifests=(tools/*/composer.json)
if (( ${#manifests[@]} == 0 )); then
    echo '::error::No tool Composer manifests found.' >&2
    exit 1
fi
manifests+=(composer.json)

for manifest in "${manifests[@]}"; do
    if [[ ! -f "$manifest" || ! -f "${manifest%.json}.lock" ]]; then
        printf '::error::Missing Composer manifest or lockfile for %s.\n' "$manifest" >&2
        exit 1
    fi
done

for manifest in "${manifests[@]}"; do
    printf 'Synchronizing %s for PHP %s -> %s\n' "$manifest" "$old_version" "$new_version"
    composer_command=(composer --working-dir="$(dirname "$manifest")" --no-interaction --no-plugins --no-scripts)
    "${composer_command[@]}" config platform.php "$new_version"
    "${composer_command[@]}" require "php:^$new_version" --no-update
    # This container has no application extensions. Core CI validates those
    # against the updated development image; Composer still checks PHP and audits.
    "${composer_command[@]}" update --lock --no-install '--ignore-platform-req=ext-*'
    "${composer_command[@]}" validate --no-check-version
done
