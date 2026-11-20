#!/usr/bin/env bash

#
# Test Make's local and CI command routing with a strict Docker mock.
#
# Usage:
#   Run from the repository root with make test-make-routing.
#
# Details:
#   Covers forwarding, forbidden commands, dry runs, environment-name isolation,
#   color propagation, and failure handling without contacting Docker.
#
# Callers:
#   Makefile:
#     - `test-make-routing` target.
#   Scripts:
#     - `scripts/tests/command-output-contract-test.sh` runs this fixture.
#

set -euo pipefail

cd "$(dirname "$0")/../../.."

# Isolate Docker calls so routing tests never contact the real daemon.
temporary=$(mktemp -d)
trap 'rm -rf "$temporary"' EXIT
export DOCKER_CALLS="$temporary/docker-calls"

cat > "$temporary/docker" <<'MOCK'
#!/usr/bin/env bash

{
    printf '%s\n' "$*"
    printf 'COLOR=%s NO_COLOR=%s PROGRESS=%s SHARED=%s\n' \
        "${COLOR:-}" "${NO_COLOR:-}" "${PROGRESS:-}" "${OUTPUT_PROGRESS_SHARED:-}"
    printf 'NATIVE_COLOR=%s\n' "${NATIVE_COLOR:-}"
} >> "$DOCKER_CALLS"

unexpected_call() {
    printf 'Unexpected Docker command: %s\n' "$original_command" >&2
    exit 64
}

original_command="$*"

case "$*" in
    info)
        exit 0
        ;;
    'version --format Docker client: {{.Client.Version}} / Engine: {{.Server.Version}}')
        exit "${DOCKER_EXIT_CODE:-0}"
        ;;
esac

[ "${1:-}" = compose ] || unexpected_call
shift

# Local and CI routing use these exact Compose prefixes.
if [ "${1:-}" = -f ]; then
    [ "$#" -ge 4 ] || unexpected_call
    [ "$1 $2 $3 $4" = '-f compose.yaml -f docker/ci-compose.yaml' ] || unexpected_call
    shift 4
fi

case "$*" in
    version)
        exit 0
        ;;
    'version --short'|'ps --services --status running')
        exit "${DOCKER_EXIT_CODE:-0}"
        ;;
    'ps --all --format {{.Service}}|{{.State}}|{{.Health}} php')
        printf 'php|running|\nnginx|running|\nmariadb|running|\n'
        exit 0
        ;;
    'exec php bash --rcfile /app/scripts/make/bashrc.sh -i')
        exit "${DOCKER_EXIT_CODE:-0}"
        ;;
esac

# Accept forwarding arguments, not arbitrary Compose execution or mutation.
[ "${1:-}" = exec ] && [ "${2:-}" = -T ] || unexpected_call
shift 2

while [ "${1:-}" = --env ]; do
    [ "$#" -ge 2 ] || unexpected_call
    [[ "$2" =~ ^[A-Z_][A-Z0-9_]*$ ]] || unexpected_call
    shift 2
done

[ "$#" -eq 4 ] || unexpected_call
[ "$1 $2 $3" = 'php bash scripts/make/make.sh' ] || unexpected_call
[[ "$4" =~ ^[a-z][a-z0-9-]*$ ]] || unexpected_call

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

# Unknown calls fail even when ordinary forwarded commands are configured to pass.
for injected_status in 0 7; do
    for unexpected in \
        'unexpected version' \
        'compose down' \
        'compose exec -T php bash scripts/unexpected.sh check-skills' \
        'compose exec -T --env CI=true php bash scripts/make/make.sh check-skills'; do
        status=0
        # Split these fixed argument fixtures as a command line, without eval.
        read -r -a arguments <<< "$unexpected"

        DOCKER_EXIT_CODE="$injected_status" "$temporary/docker" "${arguments[@]}" \
            > "$temporary/unexpected-command" 2>&1 || status=$?

        [ "$status" -eq 64 ]
        grep -Fxq "Unexpected Docker command: $unexpected" "$temporary/unexpected-command"
    done
done

# Image pin updates are host-only, including in dry runs.
: > "$DOCKER_CALLS"
host_make -n docker-image-update-pins > "$temporary/image-update-command"
grep -Fq './scripts/docker/docker-images.sh update-pins' "$temporary/image-update-command"
if grep -q 'exec -T' "$DOCKER_CALLS"; then
    echo "Docker image updates must run on the host." >&2
    exit 1
fi

if make --no-print-directory INSIDE_DOCKER_CONTAINER=1 INSIDE_CONTAINER_RUNTIME=1 \
    docker-image-update-pins > "$temporary/image-update-container" 2>&1; then
    echo "Docker image updates must reject execution inside the container." >&2
    exit 1
fi

# Registered application targets forward to the PHP container.
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
        grep -Eq 'compose exec -T .*--env BASE_SHA --env HEAD_SHA --env EVENT_NAME .*php bash scripts/make/make.sh ci-detect-changes$' "$DOCKER_CALLS"
    else
        grep -Eq "compose exec -T --env COLOR --env NO_COLOR --env NATIVE_COLOR --env PROGRESS --env OUTPUT_PROGRESS_SHARED +php bash scripts/make/make.sh $target$" "$DOCKER_CALLS"
    fi
done

# Host-side stack inventory honors color controls.
host_make tool-versions COLOR=always > "$temporary/stack-output" 2>&1
grep -Fq '[RUN]' "$temporary/stack-output"
grep -Fq $'\033[34mStack platform versions' "$temporary/stack-output"
grep -Fq '[OK]' "$temporary/stack-output"

host_make tool-versions COLOR=always NO_COLOR=1 > "$temporary/stack-plain" 2>&1
if grep -q $'\033' "$temporary/stack-plain"; then
    echo "NO_COLOR must disable host stack inventory colors." >&2
    exit 1
fi

# Interactive shells keep their host-side Compose invocation.
: > "$DOCKER_CALLS"
host_make bash > "$temporary/bash-output" 2>&1

grep -Fq 'Opening Bash in the PHP container' "$temporary/bash-output"
grep -Fq 'compose exec php bash --rcfile /app/scripts/make/bashrc.sh -i' "$DOCKER_CALLS"

# Forward native color choices, with NO_COLOR taking precedence.
: > "$DOCKER_CALLS"
host_make test-docker-images COLOR=always NO_COLOR=1

grep -q 'COLOR=always NO_COLOR=1' "$DOCKER_CALLS"
grep -q 'NATIVE_COLOR=never' "$DOCKER_CALLS"
grep -Eq 'php bash scripts/make/make.sh test-docker-images$' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make test-docker-images COLOR=always NO_COLOR=

grep -q 'NATIVE_COLOR=always' "$DOCKER_CALLS"

# Dry runs show forwarded commands without executing them.
: > "$DOCKER_CALLS"
host_make -n cache-clear clear run-tests > "$temporary/dry-run-commands"

for target in cache-clear clear run-tests; do
    grep -Fq "php bash scripts/make/make.sh $target" "$temporary/dry-run-commands"
done
if grep -q 'exec -T' "$DOCKER_CALLS"; then
    echo "Dry-run unexpectedly executed a forwarded command." >&2
    exit 1
fi

# CI forwarding selects its Compose override and passes environment names only.
: > "$DOCKER_CALLS"
GITHUB_WORKFLOW=CI DATABASE_URL=example host_make run-tests-php

grep -Eq 'compose -f compose.yaml -f docker/ci-compose.yaml exec -T .*--env DATABASE_URL .*php bash scripts/make/make.sh run-tests-php$' "$DOCKER_CALLS"
if grep -q example "$DOCKER_CALLS"; then
    echo "Environment values must not be interpolated into Docker arguments." >&2
    exit 1
fi

# A single container failure remains a Make failure.
if DOCKER_EXIT_CODE=7 host_make check-skills > "$temporary/failed-command" 2>&1; then
    echo "Container failures must propagate to Make." >&2
    exit 1
fi
grep -q 'Error 7' "$temporary/failed-command"

# Multiple forwarded goals retain color, progress, and ordering.
: > "$DOCKER_CALLS"
host_make check-skills lint-markdown COLOR=never
grep -Eq 'php bash scripts/make/make.sh check-skills$' "$DOCKER_CALLS"
grep -Eq 'php bash scripts/make/make.sh lint-markdown$' "$DOCKER_CALLS"
[ "$(grep -c 'compose exec -T' "$DOCKER_CALLS")" -eq 2 ]
grep -q 'COLOR=never NO_COLOR= PROGRESS=never SHARED=0' "$DOCKER_CALLS"

: > "$DOCKER_CALLS"
host_make check-skills PROGRESS=always
grep -q 'PROGRESS=always' "$DOCKER_CALLS"

# Mixed interactive and forwarded goals each use their own routing.
: > "$DOCKER_CALLS"
host_make bash check-skills COLOR=always > "$temporary/mixed-bash-output" 2>&1
grep -Fq 'Opening Bash in the PHP container' "$temporary/mixed-bash-output"
grep -Fq 'compose exec php bash --rcfile /app/scripts/make/bashrc.sh -i' "$DOCKER_CALLS"
grep -Eq 'php bash scripts/make/make.sh check-skills$' "$DOCKER_CALLS"

# Multiple CI goals preserve environment isolation and color policy.
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

# A failing first goal stops later forwarded goals and suppresses success.
: > "$DOCKER_CALLS"
if DOCKER_EXIT_CODE=7 host_make check-skills lint-markdown > "$temporary/failed-multiple" 2>&1; then
    echo "Multiple goals must retain a blocking container failure." >&2
    exit 1
fi
grep -Eq 'php bash scripts/make/make.sh check-skills$' "$DOCKER_CALLS"
if grep -Eq 'php bash scripts/make/make.sh lint-markdown$' "$DOCKER_CALLS"; then
    echo "Make unexpectedly continued after a blocking forwarded failure." >&2
    exit 1
fi
if grep -q '\[OK\]' "$temporary/failed-multiple"; then
    echo "Failed forwarded targets must not report success." >&2
    exit 1
fi

echo "Make container routing tests passed."
