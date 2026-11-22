#!/usr/bin/env bash

#
# Test pre-PR check selection and command routing with mocked host tools.
#
# Usage:
#   Run from the repository root with make test-command-output.
#
# Details:
#   Uses the real CI change selector with controlled changed paths. Make and
#   Docker calls are mocked; no checks, builds, or external services run.
#
# Callers:
#   scripts/tests/command-output-contract-test.sh includes this suite.
#

set -euo pipefail

cd "$(dirname "$0")/../../.."

temporary=$(mktemp -d)
trap 'rm -rf -- "$temporary"' EXIT

repository="$temporary/repository"
mkdir -p "$repository/scripts/make" "$repository/scripts/ci" "$repository/scripts/output" \
    "$repository/scripts/docker" "$temporary/bin"

cp scripts/make/pre-pr-checks.sh "$repository/scripts/make/pre-pr-checks.sh"
cp scripts/ci/ci-detect-changes.sh "$repository/scripts/ci/ci-detect-changes.sh"
cp scripts/output/command-output.sh "$repository/scripts/output/command-output.sh"

cat > "$temporary/bin/git" <<'MOCK'
#!/usr/bin/env bash
set -euo pipefail

if [[ " $* " == *' --diff-filter=D '* ]]; then
    exit 0
fi

[ "${1:-}" = diff ] || {
    printf 'Unexpected git command: %s\n' "$*" >&2
    exit 64
}
printf '%s\n' "${MOCK_CHANGED_FILES:-}"
MOCK

cat > "$temporary/bin/make" <<'MOCK'
#!/usr/bin/env bash
set -euo pipefail
printf 'make %s grouped=%s xdebug=%s\n' "$*" \
    "${OUTPUT_RUN_GROUPED:-}" "${XDEBUG_MODE:-}" >> "$PRE_PR_CALLS"
if [ "${PRE_PR_FAIL_TARGET:-}" = "${1:-}" ]; then
    exit "${PRE_PR_FAIL_STATUS:-7}"
fi
MOCK

cat > "$temporary/bin/docker" <<'MOCK'
#!/usr/bin/env bash
set -euo pipefail
printf 'docker %s grouped=%s\n' "$*" "${OUTPUT_RUN_GROUPED:-}" >> "$PRE_PR_CALLS"
MOCK

cat > "$repository/scripts/ci/ci-validate-compose.sh" <<'MOCK'
#!/usr/bin/env bash
printf 'script ci-validate-compose\n' >> "$PRE_PR_CALLS"
MOCK

cat > "$repository/scripts/docker/docker-images.sh" <<'MOCK'
#!/usr/bin/env bash
printf 'script docker-images %s grouped=%s\n' "$*" \
    "${OUTPUT_RUN_GROUPED:-}" >> "$PRE_PR_CALLS"
MOCK

chmod +x "$temporary/bin/git" "$temporary/bin/make" "$temporary/bin/docker"

export PATH="$temporary/bin:$PATH"
export XDEBUG_MODE=coverage
unset GITHUB_WORKFLOW MAKEFLAGS OUTPUT_RUN_GROUPED

run_checks() {
    local changed_files=$1
    shift

    : > "$PRE_PR_CALLS"
    PRE_PR_CALLS="$temporary/calls" MOCK_CHANGED_FILES="$changed_files" \
        bash "$repository/scripts/make/pre-pr-checks.sh" "$@" \
        > "$temporary/output" 2>&1
}

PRE_PR_CALLS="$temporary/calls"
run_checks README.md
grep -Fqx 'make lint-markdown grouped= xdebug=coverage' "$PRE_PR_CALLS"
grep -Fqx 'make check-skills grouped= xdebug=coverage' "$PRE_PR_CALLS"
[ "$(grep -c '^make ' "$PRE_PR_CALLS")" -eq 2 ]
if grep -Eq 'test-command-output|^docker ' "$PRE_PR_CALLS"; then
    printf 'Documentation-only selection ran unrelated tooling or Docker checks.\n' >&2
    exit 1
fi

MAKEFLAGS=--no-print-directory run_checks README.md
[ "$(grep -c '^make ' "$PRE_PR_CALLS")" -eq 2 ]

MAKEFLAGS='n --no-print-directory' run_checks README.md
grep -Fq 'Skipping pre-PR checks in Make dry-run mode.' "$temporary/output"
[ ! -s "$PRE_PR_CALLS" ]

run_checks scripts/example.py
grep -Fqx 'make test-command-output grouped= xdebug=coverage' "$PRE_PR_CALLS"
grep -Fqx 'make lint-python grouped=1 xdebug=coverage' "$PRE_PR_CALLS"
grep -Fqx 'make typecheck-python grouped=1 xdebug=coverage' "$PRE_PR_CALLS"
grep -Fq 'docker compose exec -T php python3 scripts/checks/tests/check-workflow-tool-updates-test.py' \
    "$PRE_PR_CALLS"
[ "$(grep -c '^make ' "$PRE_PR_CALLS")" -eq 3 ]

status=0
: > "$PRE_PR_CALLS"
PRE_PR_CALLS="$temporary/calls" PRE_PR_FAIL_TARGET=lint-python \
    MOCK_CHANGED_FILES=scripts/example.py \
    bash "$repository/scripts/make/pre-pr-checks.sh" > "$temporary/output" 2>&1 || status=$?
[ "$status" -eq 7 ]
grep -Fq 'make lint-python grouped=1 xdebug=coverage' "$PRE_PR_CALLS"
if grep -Fq 'make typecheck-python' "$PRE_PR_CALLS"; then
    printf 'A failed selected check did not stop subsequent checks.\n' >&2
    exit 1
fi

status=0
bash "$repository/scripts/make/pre-pr-checks.sh" --invalid \
    > "$temporary/output" 2>&1 || status=$?
[ "$status" -eq 64 ]

run_checks '' --all
for target in \
    application-info check-requirements composer-validate lint-yaml test-command-output \
    lint-python typecheck-python lint-markdown check-skills \
    lint-workflows check-security test-docker-images docker-image-verify-pins \
    phpcs ecs phplint php-parallel-lint phploc psalm phpstan phpinsights \
    run-tests-php phpmetrics; do
    grep -Eq "^make ${target} .*" "$PRE_PR_CALLS"
done
grep -Fq 'script ci-validate-compose' "$PRE_PR_CALLS"
grep -Fq 'script docker-images check-updates --advisory grouped=1' "$PRE_PR_CALLS"
[ "$(grep -c '^docker buildx build ' "$PRE_PR_CALLS")" -eq 2 ]

printf 'Pre-PR check selection tests passed.\n'
