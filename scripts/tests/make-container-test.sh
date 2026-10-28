#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."
temporary=$(mktemp -d)
trap 'rm -rf "$temporary"' EXIT
export DOCKER_CALLS="$temporary/docker-calls"

cat > "$temporary/docker" <<'MOCK'
#!/usr/bin/env bash
printf '%s\n' "$*" >> "$DOCKER_CALLS"
if [ "${1:-}" = ps ]; then
    printf 'symfony-backend\n%.0s' {1..5}
fi
exit "${DOCKER_EXIT_CODE:-0}"
MOCK
chmod +x "$temporary/docker"
export PATH="$temporary:$PATH"
unset GITHUB_WORKFLOW DATABASE_URL

host_make() {
    make --no-print-directory INSIDE_DOCKER_CONTAINER=0 INSIDE_CONTAINER_RUNTIME=0 "$@"
}

for target in cache-clear cache-warmup clear clear-tools run-tests check-skills lint-workflows \
    lint-markdown composer-validate install application-info check-requirements \
    tool-versions report-coveralls generate-ssl-cert; do
    : > "$DOCKER_CALLS"
    host_make "$target"
    grep -Eq "compose exec -T +php make $target$" "$DOCKER_CALLS"
done

: > "$DOCKER_CALLS"
host_make -n cache-clear clear run-tests
if grep -q 'exec -T' "$DOCKER_CALLS"; then
    echo "Dry-run unexpectedly executed a forwarded command." >&2
    exit 1
fi

: > "$DOCKER_CALLS"
GITHUB_WORKFLOW=CI DATABASE_URL=example host_make run-tests-php
grep -Eq 'compose -f compose.yaml -f docker/ci-compose.yaml exec -T .*--env DATABASE_URL .*php make run-tests-php$' "$DOCKER_CALLS"
if grep -q example "$DOCKER_CALLS"; then
    echo "Environment values must not be interpolated into Docker arguments." >&2
    exit 1
fi

if DOCKER_EXIT_CODE=7 host_make check-skills > "$temporary/failed-command" 2>&1; then
    echo "Container failures must propagate to Make." >&2
    exit 1
fi
grep -q 'Error 7' "$temporary/failed-command"

echo "Make container routing tests passed."
