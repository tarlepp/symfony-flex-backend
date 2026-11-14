#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."
temporary=$(mktemp -d)
trap 'rm -rf "$temporary"' EXIT
export DOCKER_CALLS="$temporary/docker-calls"

cat > "$temporary/docker" <<'MOCK'
#!/usr/bin/env bash
printf '%s\n' "$*" >> "$DOCKER_CALLS"
printf 'COLOR=%s NO_COLOR=%s PROGRESS=%s SHARED=%s\n' "${COLOR:-}" "${NO_COLOR:-}" "${PROGRESS:-}" "${OUTPUT_PROGRESS_SHARED:-}" >> "$DOCKER_CALLS"
printf 'NATIVE_COLOR=%s\n' "${NATIVE_COLOR:-}" >> "$DOCKER_CALLS"
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
unset PROGRESS OUTPUT_PROGRESS_SHARED GITHUB_WORKFLOW DATABASE_URL CI XDEBUG_MODE COVERALLS_REPO_TOKEN \
    COVERALLS_RUN_LOCALLY GITHUB_TOKEN GITHUB_ACTIONS GITHUB_RUN_ID GITHUB_RUN_NUMBER GITHUB_JOB \
    GITHUB_REF GITHUB_SHA GITHUB_HEAD_REF GITHUB_REPOSITORY GITHUB_EVENT_NAME GITHUB_BASE_REF \
    GITHUB_SERVER_URL

host_make() {
    make --no-print-directory INSIDE_DOCKER_CONTAINER=0 INSIDE_CONTAINER_RUNTIME=0 "$@"
}

: > "$DOCKER_CALLS"
host_make -n docker-image-update-pins > "$temporary/image-update-command"
grep -Fq './scripts/docker-images.sh update-pins' "$temporary/image-update-command"
if grep -q 'exec -T' "$DOCKER_CALLS"; then
    echo "Docker image updates must run on the host." >&2
    exit 1
fi
if make --no-print-directory INSIDE_DOCKER_CONTAINER=1 INSIDE_CONTAINER_RUNTIME=1 \
    docker-image-update-pins > "$temporary/image-update-container" 2>&1; then
    echo "Docker image updates must reject execution inside the container." >&2
    exit 1
fi

for target in ci-detect-changes cache-clear cache-warmup clear clear-tools run-tests check-skills lint-workflows \
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
    if [ "$target" = ci-detect-changes ]; then
        grep -Eq 'compose exec -T .*--env BASE_SHA --env HEAD_SHA --env EVENT_NAME .*php bash scripts/make.sh ci-detect-changes$' "$DOCKER_CALLS"
    else
        grep -Eq "compose exec -T --env COLOR --env NO_COLOR --env NATIVE_COLOR --env PROGRESS --env OUTPUT_PROGRESS_SHARED +php bash scripts/make.sh $target$" "$DOCKER_CALLS"
    fi
done

host_make tool-versions COLOR=always > "$temporary/stack-output" 2>&1
grep -Fq '[RUN]' "$temporary/stack-output"
grep -Fq $'\033[34mStack platform versions' "$temporary/stack-output"
grep -Fq '[OK]' "$temporary/stack-output"
host_make tool-versions COLOR=always NO_COLOR=1 > "$temporary/stack-plain" 2>&1
if grep -q $'\033' "$temporary/stack-plain"; then
    echo "NO_COLOR must disable host stack inventory colors." >&2
    exit 1
fi

: > "$DOCKER_CALLS"
host_make bash > "$temporary/bash-output" 2>&1
grep -Fq 'Opening Bash in the PHP container' "$temporary/bash-output"
grep -Fq 'compose exec php bash --rcfile /app/scripts/bashrc.sh -i' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make test-docker-images COLOR=always NO_COLOR=1
grep -q 'COLOR=always NO_COLOR=1' "$DOCKER_CALLS"
grep -q 'NATIVE_COLOR=never' "$DOCKER_CALLS"
grep -Eq 'php bash scripts/make.sh test-docker-images$' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make test-docker-images COLOR=always NO_COLOR=
grep -q 'NATIVE_COLOR=always' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make -n cache-clear clear run-tests > "$temporary/dry-run-commands"
for target in cache-clear clear run-tests; do
    grep -Fq "php bash scripts/make.sh $target" "$temporary/dry-run-commands"
done
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
grep -q 'COLOR=never NO_COLOR= PROGRESS=never SHARED=0' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make check-skills PROGRESS=always
grep -q 'PROGRESS=always' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make bash check-skills COLOR=always > "$temporary/mixed-bash-output" 2>&1
grep -Fq 'Opening Bash in the PHP container' "$temporary/mixed-bash-output"
grep -Fq 'compose exec php bash --rcfile /app/scripts/bashrc.sh -i' "$DOCKER_CALLS"
grep -Eq 'php bash scripts/make.sh check-skills$' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
GITHUB_WORKFLOW=CI CI=true XDEBUG_MODE=coverage host_make check-skills lint-markdown COLOR=always NO_COLOR=1
[ "$(grep -c 'compose -f compose.yaml -f docker/ci-compose.yaml exec -T' "$DOCKER_CALLS")" -eq 2 ]
grep -q -- '--env CI' "$DOCKER_CALLS"
grep -q -- '--env XDEBUG_MODE' "$DOCKER_CALLS"
grep -q 'COLOR=always NO_COLOR=1 PROGRESS=never' "$DOCKER_CALLS"
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
