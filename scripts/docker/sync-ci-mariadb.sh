#!/usr/bin/env bash

#
# Synchronize the core CI MariaDB service pin with the MariaDB Dockerfile.
#
# Usage:
#   From the repository root on the GitHub runner:
#     bash scripts/docker/sync-ci-mariadb.sh
#
# Details:
#   Validates both pins before updating the service tag and digest, including
#   digest-only changes and existing drift.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml`.
#

set -euo pipefail

dockerfile=docker/mariadb/Dockerfile
workflow=.github/workflows/main.yml
pin=$(sed -nE 's/^FROM (mariadb:[0-9]+\.[0-9]+\.[0-9]+@sha256:[a-f0-9]{64})$/\1/p' "$dockerfile")

if ! [[ "$pin" =~ ^mariadb:[0-9]+\.[0-9]+\.[0-9]+@sha256:[a-f0-9]{64}$ ]]; then
    echo '::error::Expected exactly one pinned MariaDB image in the Dockerfile.'
    exit 1
fi

if [[ $(grep -Ec '^[[:space:]]*image:[[:space:]]*mariadb:' "$workflow") != 1 ]] ||
    ! grep -Eq '^        image: mariadb:[0-9]+\.[0-9]+\.[0-9]+@sha256:[a-f0-9]{64}$' "$workflow"; then
    echo '::error::Expected exactly one pinned MariaDB service image in the CI workflow.'
    exit 1
fi

sed -i -E "s|^(        image: )mariadb:[0-9]+\.[0-9]+\.[0-9]+@sha256:[a-f0-9]{64}$|\1${pin}|" "$workflow"

echo "CI MariaDB service synchronized to $pin."
