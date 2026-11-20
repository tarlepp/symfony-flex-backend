#!/usr/bin/env bash

#
# Validate repository Compose variants and Dev Container JSON.
#
# Usage:
#   Run on the Docker host from any directory.
#
# Details:
#   Compose configurations are checked on the host; JSON validation uses Python
#   in the CI PHP service.
#

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

docker compose config --quiet
docker compose -f compose.yaml -f compose.prod.yaml config --quiet
docker compose -f compose.yaml -f docker/ci-compose.yaml config --quiet
docker compose -f compose.yaml -f .devcontainer/docker-compose.devcontainer.yml config --quiet
docker compose -f compose.yaml -f docker/ci-compose.yaml exec -T php \
    python3 -m json.tool .devcontainer/devcontainer.json /dev/null
