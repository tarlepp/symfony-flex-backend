#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

docker compose config --quiet
docker compose -f compose.yaml -f compose.prod.yaml config --quiet
docker compose -f compose.yaml -f docker/ci-compose.yaml config --quiet
docker compose -f compose.yaml -f .devcontainer/docker-compose.devcontainer.yml config --quiet
docker compose -f compose.yaml -f docker/ci-compose.yaml exec -T php \
    python3 -m json.tool .devcontainer/devcontainer.json /dev/null
