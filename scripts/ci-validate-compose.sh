#!/usr/bin/env bash

# Validate repository Compose variants and the Dev Container JSON configuration.
# Run from any directory; Compose checks execute on the Docker host, while JSON
# validation uses Python in the CI PHP service.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

docker compose config --quiet
docker compose -f compose.yaml -f compose.prod.yaml config --quiet
docker compose -f compose.yaml -f docker/ci-compose.yaml config --quiet
docker compose -f compose.yaml -f .devcontainer/docker-compose.devcontainer.yml config --quiet
docker compose -f compose.yaml -f docker/ci-compose.yaml exec -T php \
    python3 -m json.tool .devcontainer/devcontainer.json /dev/null
