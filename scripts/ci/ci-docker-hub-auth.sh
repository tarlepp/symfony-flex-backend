#!/usr/bin/env bash

#
# Authenticate to Docker Hub for CI image maintenance when credentials exist.
#
# Usage:
#   bash scripts/ci/ci-docker-hub-auth.sh ANONYMOUS_NOTICE
#
# Details:
#   DOCKERHUB_USERNAME and DOCKERHUB_TOKEN must both be set or both be empty.
#   The token is passed to Docker on stdin; missing credentials use anonymous
#   requests. Docker login failures are returned to the calling workflow.
#
# Callers:
#   `.github/workflows/main.yml` and `.github/workflows/docker-image-updates.yml`
#

set -euo pipefail

if (( $# != 1 )) || [ -z "$1" ]; then
    echo "Usage: $0 ANONYMOUS_NOTICE" >&2
    exit 2
fi

if [ -n "${DOCKERHUB_USERNAME:-}" ] && [ -n "${DOCKERHUB_TOKEN:-}" ]; then
    printf '%s' "$DOCKERHUB_TOKEN" | docker login docker.io --username "$DOCKERHUB_USERNAME" --password-stdin
elif [ -n "${DOCKERHUB_USERNAME:-}" ] || [ -n "${DOCKERHUB_TOKEN:-}" ]; then
    echo "::error::Configure both DOCKERHUB_USERNAME and DOCKERHUB_TOKEN, or neither."
    exit 1
else
    printf '::notice::%s\n' "$1"
fi
