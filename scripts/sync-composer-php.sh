#!/usr/bin/env bash

#
# Synchronize Composer's PHP platform requirement with the pinned PHP image.
#
# Usage:
#   From the repository root on the Docker host, for patch synchronization:
#     git show HEAD:Dockerfile |
#       docker compose -f compose.yaml -f docker/ci-compose.yaml exec -T \
#         -e COMPOSER_HOME=/tmp/composer php bash scripts/sync-composer-php.sh
#   Candidate minor readiness invokes it internally through:
#     bash scripts/docker/docker-images.sh check-php-minor --prepare
#   That flow passes --next-minor in the candidate development image.
#
# Inputs:
#   Reads the old Dockerfile from stdin and the proposed Dockerfile from the
#   current project directory.
#
# Details:
#   Default mode accepts patch updates and detects baseline drift when the tag
#   is unchanged; --next-minor accepts only the immediately following minor in
#   the same major. Updates verify locked package versions and source/dist
#   references remain unchanged.
#
# Requirements:
#   Bash, Composer, jq, and valid Composer manifests and lockfiles.
#
# Callers:
#   Scripts:
#     - `scripts/docker/docker-images.sh` runs --next-minor in the candidate
#       image when preparing PHP minor readiness.
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml` runs patch mode in the
#       maintenance container.
#

set -euo pipefail

mode=${1:-patch}

if (( $# > 1 )) || [[ "$mode" != patch && "$mode" != --next-minor ]]; then
    echo '::error::Expected no arguments or --next-minor.' >&2
    exit 64
fi

php_version() {
    sed -nE 's/^FROM php:([0-9]+\.[0-9]+\.[0-9]+)-[^[:space:]@]+@sha256:[a-f0-9]{64} AS php-upstream$/\1/p' "$@"
}

# Read the old pin from stdin and the proposed pin from the current Dockerfile.
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

if [[ "$mode" == patch && "$old_version" == "$new_version" ]]; then
    for manifest in "${manifests[@]}"; do
        lockfile="${manifest%.json}.lock"
        if ! jq -e --arg version "$new_version" \
            '.require.php == ("^" + $version) and .config.platform.php == $version' \
            "$manifest" > /dev/null ||
            ! jq -e --arg version "$new_version" \
                '.platform.php == ("^" + $version) and .["platform-overrides"].php == $version' \
                "$lockfile" > /dev/null; then
            printf '::error::PHP baseline drift or invalid JSON in %s or %s; review before updating.\n' \
                "$manifest" "$lockfile" >&2
            exit 1
        fi

        composer --working-dir="$(dirname "$manifest")" --no-interaction --no-plugins --no-scripts \
            validate --no-check-version
    done
    echo "PHP tag unchanged ($new_version); Composer baselines and lockfiles verified."
    exit 0
fi

# Compare only package identity and source/dist references; platform metadata
# may legitimately change when the PHP runtime requirement is synchronized.
locked_packages() {
    jq -ce 'if (.packages | type) != "array" or (.["packages-dev"] | type) != "array" then
        error("Missing locked packages")
        else [.packages, .["packages-dev"]] | map(map({name, version, source: .source.reference, dist: .dist.reference}))
        end' "$1"
}

for manifest in "${manifests[@]}"; do
    printf 'Synchronizing %s for PHP %s -> %s\n' "$manifest" "$old_version" "$new_version"
    composer_command=(composer --working-dir="$(dirname "$manifest")" --no-interaction --no-plugins --no-scripts)
    before="$(locked_packages "${manifest%.json}.lock")"
    "${composer_command[@]}" config platform.php "$new_version"
    "${composer_command[@]}" require "php:^$new_version" --no-update
    "${composer_command[@]}" "${lock_args[@]}"
    "${composer_command[@]}" validate --no-check-version
    after="$(locked_packages "${manifest%.json}.lock")"
    if [[ "$before" != "$after" ]]; then
        printf '::error::Package versions or references changed in %s.\n' "${manifest%.json}.lock" >&2
        exit 1
    fi
done
