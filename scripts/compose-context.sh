#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/command-output.sh"

mode=${1:-}
if [ "$#" -lt 2 ] || [[ ! "$mode" =~ ^(php|logs|idle)$ ]]; then
    output_status FAIL 'Usage: compose-context.sh <php|logs|idle> <docker compose command...>'
    exit 64
fi
shift
if ! command -v docker >/dev/null 2>&1; then
    output_status FAIL 'Docker command is unavailable; run this target on a Docker host'
    exit 127
fi
if ! docker info >/dev/null; then
    output_status FAIL 'Docker daemon is unavailable or inaccessible; check Docker and permissions'
    exit 1
fi
if ! "$@" version >/dev/null; then
    output_status FAIL 'Docker Compose is unavailable; check the Compose plugin'
    exit 1
fi

services=()
case "$mode" in
    php) services=(php) ;;
    logs) services=(php nginx mariadb) ;;
esac
if statuses=$("$@" ps --all --format '{{.Service}}|{{.State}}|{{.Health}}' "${services[@]}"); then
    :
else
    status=$?
    output_status FAIL "Could not inspect Compose services (exit $status)"
    exit "$status"
fi
found=false
running=false
while IFS='|' read -r service state health; do
    [ -n "$service" ] || continue
    case "$mode" in
        php) [ "$service" = php ] || continue ;;
        logs) [[ "$service" =~ ^(php|nginx|mariadb)$ ]] || continue ;;
    esac
    found=true
    if [ "$state" = running ]; then
        running=true
    fi
    if [ "$mode" = idle ] && [ "$state" = running ]; then
        output_status FAIL "Compose service '$service' is already running; stop this project before starting again"
        exit 1
    fi
    if [ "$mode" = php ] && [ "$state" = running ] && [ -n "$health" ] && [ "$health" != healthy ]; then
        output_status WARN "PHP service health is '$health'; access remains available for troubleshooting"
    fi
done <<< "$statuses"
if [ "$mode" = php ] && [ "$running" = false ]; then
    if [ "$found" = true ]; then
        output_status FAIL 'PHP Compose service is stopped or not running; inspect Compose logs and start it'
    else
        output_status FAIL 'PHP Compose service is absent; start the development or CI service first'
    fi
    exit 1
fi
if [ "$mode" = logs ] && [ "$found" = false ]; then
    output_status FAIL 'No PHP, nginx or MariaDB containers exist in this Compose project; no logs are available'
    exit 1
fi
