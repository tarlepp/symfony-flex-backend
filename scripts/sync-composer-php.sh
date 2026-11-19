#!/usr/bin/env bash
# Run in the PHP container with the original Dockerfile on stdin.
# --next-minor requires the candidate development image with its extensions.
set -euo pipefail

mode=${1:-patch}
if (( $# > 1 )) || [[ "$mode" != patch && "$mode" != --next-minor ]]; then
    echo '::error::Expected no arguments or --next-minor.' >&2
    exit 64
fi

php_version() {
    sed -nE 's/^FROM php:([0-9]+\.[0-9]+\.[0-9]+)-[^[:space:]@]+@sha256:[a-f0-9]{64} AS php-upstream$/\1/p' "$@"
}

old_version=$(php_version)
new_version=$(php_version Dockerfile)
version_pattern='^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$'

if ! [[ "$old_version" =~ $version_pattern && "$new_version" =~ $version_pattern ]]; then
    echo '::error::Expected exactly one pinned PHP version in each Dockerfile.' >&2
    exit 1
fi

lock_args=(update --lock --no-install)
if [[ "$mode" == --next-minor ]]; then
    IFS=. read -r old_major old_minor _ <<< "$old_version"
    IFS=. read -r new_major new_minor _ <<< "$new_version"
    if [[ "$old_major" != "$new_major" || "$new_minor" != "$((old_minor + 1))" ]]; then
        echo '::error::Only the immediately following PHP minor in the same major is allowed.' >&2
        exit 1
    fi
else
    if [[ "$old_version" == "$new_version" ]]; then
        echo "PHP tag unchanged ($new_version); no Composer refresh needed."
        exit 0
    fi
    if [[ "${old_version%.*}" != "${new_version%.*}" ]]; then
        echo '::error::The routine updater can only apply PHP patch updates.' >&2
        exit 1
    fi
    # The patch updater runs in the maintenance image without app extensions.
    lock_args+=('--ignore-platform-req=ext-*')
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

locked_packages() {
    jq -ce 'if (.packages | type) != "array" or (.["packages-dev"] | type) != "array" then
        error("Missing locked packages")
        else [.packages, .["packages-dev"]] | map(map({name, version, source: .source.reference, dist: .dist.reference}))
        end' "$1"
}

for manifest in "${manifests[@]}"; do
    printf 'Synchronizing %s for PHP %s -> %s\n' "$manifest" "$old_version" "$new_version"
    composer_command=(composer --working-dir="$(dirname "$manifest")" --no-interaction --no-plugins --no-scripts)
    if [[ "$mode" == --next-minor ]]; then
        before="$(locked_packages "${manifest%.json}.lock")"
    fi
    "${composer_command[@]}" config platform.php "$new_version"
    "${composer_command[@]}" require "php:^$new_version" --no-update
    "${composer_command[@]}" "${lock_args[@]}"
    "${composer_command[@]}" validate --no-check-version
    if [[ "$mode" == --next-minor ]]; then
        after="$(locked_packages "${manifest%.json}.lock")"
        if [[ "$before" != "$after" ]]; then
            printf '::error::Package versions or references changed in %s.\n' "${manifest%.json}.lock" >&2
            exit 1
        fi
    fi
done
