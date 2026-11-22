#!/usr/bin/env bash

#
# Synchronize Doctrine's MariaDB server version after a same-major tag update.
#
# Usage:
#   From the repository root on the GitHub runner:
#     bash scripts/docker/sync-doctrine-mariadb.sh
#
# Details:
#   Compares the checked-out Dockerfile with HEAD, rejects major upgrades and
#   changes only the explicit server_version in the Doctrine configuration.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml`.
#

set -euo pipefail

dockerfile=docker/mariadb/Dockerfile
directive='s/^FROM mariadb:([0-9]+\.[0-9]+\.[0-9]+)@sha256:[a-f0-9]{64}$/\1/p'
previous=$(git show "HEAD:$dockerfile" | sed -nE "$directive")
candidate=$(sed -nE "$directive" "$dockerfile")
version_pattern='^[0-9]+\.[0-9]+\.[0-9]+$'

if ! [[ "$previous" =~ $version_pattern && "$candidate" =~ $version_pattern ]]; then
    echo '::error::Expected exactly one pinned MariaDB version in each Dockerfile.'
    exit 1
fi

if [[ "$previous" == "$candidate" ]]; then
    echo "MariaDB tag unchanged ($candidate); no Doctrine update needed."
    exit 0
fi

if [[ "${previous%%.*}" != "${candidate%%.*}" ]]; then
    echo '::error::MariaDB major upgrades require manual review.'
    exit 1
fi

config=config/packages/doctrine.yaml

if [[ $(grep -Ec '^[[:space:]]*server_version:' "$config") != 1 ]] ||
    ! grep -Eq "^        server_version: 'mariadb-[0-9]+\.[0-9]+\.[0-9]+'$" "$config"; then
    echo '::error::Expected exactly one explicit MariaDB server_version in Doctrine configuration.'
    exit 1
fi

sed -i -E "s/^(        server_version: )'mariadb-[0-9]+\.[0-9]+\.[0-9]+'$/\1'mariadb-$candidate'/" "$config"

echo "Doctrine server_version synchronized to mariadb-$candidate."
