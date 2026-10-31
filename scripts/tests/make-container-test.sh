#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."
temporary=$(mktemp -d)
trap 'rm -rf "$temporary"' EXIT
export DOCKER_CALLS="$temporary/docker-calls"

cat > "$temporary/docker" <<'MOCK'
#!/usr/bin/env bash
printf '%s\n' "$*" >> "$DOCKER_CALLS"
printf 'COLOR=%s NO_COLOR=%s\n' "${COLOR:-}" "${NO_COLOR:-}" >> "$DOCKER_CALLS"
if [ "${1:-}" = ps ]; then
    printf 'symfony-backend\n%.0s' {1..5}
fi
if [[ "$*" == *" ps --all --format "* ]]; then
    printf 'php|running|\nnginx|running|\nmariadb|running|\n'
    exit 0
fi
if [ "${1:-}" = info ] || [[ "$*" == *" version" ]]; then exit 0; fi
exit "${DOCKER_EXIT_CODE:-0}"
MOCK
chmod +x "$temporary/docker"
export PATH="$temporary:$PATH"
unset GITHUB_WORKFLOW DATABASE_URL CI XDEBUG_MODE COVERALLS_REPO_TOKEN \
    COVERALLS_RUN_LOCALLY GITHUB_TOKEN GITHUB_ACTIONS GITHUB_RUN_ID GITHUB_RUN_NUMBER GITHUB_JOB \
    GITHUB_REF GITHUB_SHA GITHUB_HEAD_REF GITHUB_REPOSITORY GITHUB_EVENT_NAME GITHUB_BASE_REF \
    GITHUB_SERVER_URL

host_make() {
    make --no-print-directory INSIDE_DOCKER_CONTAINER=0 INSIDE_CONTAINER_RUNTIME=0 "$@"
}

for target in cache-clear cache-warmup clear clear-tools run-tests check-skills lint-workflows \
    lint-markdown composer-validate install application-info check-requirements \
    tool-versions report-coveralls generate-ssl-cert prepare-sonar-reports test-command-output \
    configuration generate-jwt-keys run-tests-php run-tests-phpdbg infection phpmetrics phpcs \
    ecs ecs-fix phpinsights phplint php-parallel-lint psalm psalm-shepherd psalm-github \
    phpstan phpstan-github lint-configuration lint-yaml check-dependencies-latest \
    check-dependencies-minor check-dependencies-patch check-licenses update update-tools \
    install-tools local-configuration composer-normalize composer-require-checker composer-unused \
    phploc check-security check-action-updates project-stats configure-ci; do
    : > "$DOCKER_CALLS"
    host_make "$target"
    grep -Eq "compose exec -T --env COLOR --env NO_COLOR +php bash scripts/make.sh $target$" "$DOCKER_CALLS"
done

: > "$DOCKER_CALLS"
host_make bash
grep -Fq 'compose exec php bash --rcfile /app/scripts/bashrc.sh -i' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make test-docker-images COLOR=always NO_COLOR=1
grep -q 'COLOR=always NO_COLOR=1' "$DOCKER_CALLS"
grep -Eq 'php bash scripts/make.sh test-docker-images$' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make -n cache-clear clear run-tests
if grep -q 'exec -T' "$DOCKER_CALLS"; then
    echo "Dry-run unexpectedly executed a forwarded command." >&2
    exit 1
fi

: > "$DOCKER_CALLS"
GITHUB_WORKFLOW=CI DATABASE_URL=example host_make run-tests-php
grep -Eq 'compose -f compose.yaml -f docker/ci-compose.yaml exec -T .*--env DATABASE_URL .*php bash scripts/make.sh run-tests-php$' "$DOCKER_CALLS"
if grep -q example "$DOCKER_CALLS"; then
    echo "Environment values must not be interpolated into Docker arguments." >&2
    exit 1
fi

if DOCKER_EXIT_CODE=7 host_make check-skills > "$temporary/failed-command" 2>&1; then
    echo "Container failures must propagate to Make." >&2
    exit 1
fi
grep -q 'Error 7' "$temporary/failed-command"

: > "$DOCKER_CALLS"
host_make check-skills lint-markdown COLOR=never
grep -Eq 'php bash scripts/make.sh check-skills$' "$DOCKER_CALLS"
grep -Eq 'php bash scripts/make.sh lint-markdown$' "$DOCKER_CALLS"
[ "$(grep -c 'compose exec -T' "$DOCKER_CALLS")" -eq 2 ]
grep -q 'COLOR=never NO_COLOR=' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make bash check-skills COLOR=always
grep -Fq 'compose exec php bash --rcfile /app/scripts/bashrc.sh -i' "$DOCKER_CALLS"
grep -Eq 'php bash scripts/make.sh check-skills$' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
GITHUB_WORKFLOW=CI CI=true XDEBUG_MODE=coverage host_make check-skills lint-markdown COLOR=always NO_COLOR=1
[ "$(grep -c 'compose -f compose.yaml -f docker/ci-compose.yaml exec -T' "$DOCKER_CALLS")" -eq 2 ]
grep -q -- '--env CI' "$DOCKER_CALLS"
grep -q -- '--env XDEBUG_MODE' "$DOCKER_CALLS"
grep -q 'COLOR=always NO_COLOR=1' "$DOCKER_CALLS"
if grep -q -- '--env CI=true\|--env XDEBUG_MODE=coverage' "$DOCKER_CALLS"; then
    echo "CI environment values must not be interpolated into Docker arguments." >&2
    exit 1
fi

: > "$DOCKER_CALLS"
if DOCKER_EXIT_CODE=7 host_make check-skills lint-markdown > "$temporary/failed-multiple" 2>&1; then
    echo "Multiple goals must retain a blocking container failure." >&2
    exit 1
fi
grep -Eq 'php bash scripts/make.sh check-skills$' "$DOCKER_CALLS"
if grep -Eq 'php bash scripts/make.sh lint-markdown$' "$DOCKER_CALLS"; then
    echo "Make unexpectedly continued after a blocking forwarded failure." >&2
    exit 1
fi
if grep -q '\[OK\]' "$temporary/failed-multiple"; then
    echo "Failed forwarded targets must not report success." >&2
    exit 1
fi

echo "Make container routing tests passed."
