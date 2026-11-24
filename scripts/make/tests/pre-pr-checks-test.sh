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
cp scripts/output/native-output.py "$repository/scripts/output/native-output.py"

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
printf 'make %s grouped=%s xdebug=%s run_target=%s\n' "$*" \
    "${OUTPUT_RUN_GROUPED:-}" "${XDEBUG_MODE:-}" "${OUTPUT_RUN_TARGET:-}" >> "$PRE_PR_CALLS"
if [ "${1:-}" = ci-detect-changes ]; then
    GITHUB_OUTPUT="$PWD/${GITHUB_OUTPUT#/app/}" bash scripts/ci/ci-detect-changes.sh
    exit "$?"
fi
if [ "${PRE_PR_FAIL_TARGET:-}" = "${1:-}" ]; then
    exit "${PRE_PR_FAIL_STATUS:-7}"
fi
if [ "${1:-}" = typecheck-python ]; then
    printf '[OK] Check Python types (make typecheck-python)\n' >&2
fi
if [ "${1:-}" = application-info ] || [ "${1:-}" = lint-yaml ]; then
    printf '  [2026-11-24T11:32:04.469665+00:00] deprecation.INFO: unwanted log\n' >&2
    printf '  [2026-11-24T11:32:04.469665+00:00] deprecation.WARNING: visible warning\n' >&2
    printf 'visible diagnostic\n' >&2
fi
if [ "${1:-}" = test-docker-images ]; then
    printf '  [OK] Major reporting stays separate from automatic pin updates\n' >&2
    printf '[OK] Docker image tests: 98 scenarios passed\n' >&2
fi
if [[ "${1:-}" = docker-build-pre-pr-* ]]; then
    variant=${1#docker-build-pre-pr-}
    printf '[RUN] Build %s Docker image (make %s)\n' "$variant" "$1" >&2
    printf '  build output\n' >&2
    printf '[OK] Build %s Docker image (make %s)\n' "$variant" "$1" >&2
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
export COLOR=never NO_COLOR=1 PROGRESS=never
unset GITHUB_WORKFLOW MAKEFLAGS OUTPUT_RUN_GROUPED OUTPUT_RUN_GROUPED_SEPARATOR \
    OUTPUT_RUN_GROUPED_STARTED OUTPUT_RUN_TARGET OUTPUT_RUN_TARGET_ACTIVE \
    OUTPUT_STATUS_INDENT OUTPUT_SUCCESS_STATUS OUTPUT_SUCCESS_MESSAGE

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
grep -Fqx 'make lint-markdown grouped=1 xdebug=coverage run_target=lint-markdown' "$PRE_PR_CALLS"
grep -Fqx 'make check-skills grouped=1 xdebug=coverage run_target=check-skills' "$PRE_PR_CALLS"
grep -Fqx 'make ci-detect-changes grouped= xdebug=coverage run_target=ci-detect-changes' "$PRE_PR_CALLS"
[ "$(grep -c '^make ' "$PRE_PR_CALLS")" -eq 3 ]
if grep -Eq 'test-command-output|^docker ' "$PRE_PR_CALLS"; then
    printf 'Documentation-only selection ran unrelated tooling or Docker checks.\n' >&2
    exit 1
fi

MAKEFLAGS=--no-print-directory run_checks README.md
[ "$(grep -c '^make ' "$PRE_PR_CALLS")" -eq 3 ]

MAKEFLAGS='n --no-print-directory' run_checks README.md
grep -Fq 'Skipping pre-PR checks in Make dry-run mode.' "$temporary/output"
[ ! -s "$PRE_PR_CALLS" ]

run_checks scripts/example.py
grep -Fqx 'make test-command-output grouped=1 xdebug=coverage run_target=test-command-output' "$PRE_PR_CALLS"
grep -Fqx 'make lint-python grouped=1 xdebug=coverage run_target=lint-python' "$PRE_PR_CALLS"
grep -Fqx 'make typecheck-python grouped=1 xdebug=coverage run_target=typecheck-python' "$PRE_PR_CALLS"
grep -Fqx 'make ci-detect-changes grouped= xdebug=coverage run_target=ci-detect-changes' "$PRE_PR_CALLS"
grep -Fq 'docker compose exec -T php env -u OUTPUT_RUN_GROUPED python3 scripts/tests/run-workflow-tests.py scripts/checks/tests/check-workflow-tool-updates-test.py' \
    "$PRE_PR_CALLS"
[ "$(grep -c '^make ' "$PRE_PR_CALLS")" -eq 4 ]

run_checks $'scripts/example.py\ncompose.yaml'
if ! awk '
    $0 == "[OK] Check Python types (make typecheck-python)" {
        previous = 1
        next
    }
    previous && $0 == "" {
        blank_lines++
        next
    }
    previous {
        if ($0 == "[RUN] Validate Compose configuration" && blank_lines == 1) {
            found = 1
        }
        previous = 0
        blank_lines = 0
    }
    END { if (!found) exit 1 }
' "$temporary/output"; then
    printf 'Expected one blank line between Python type checking and Compose validation:\n' >&2
    cat "$temporary/output" >&2
    exit 1
fi

status=0
: > "$PRE_PR_CALLS"
PRE_PR_CALLS="$temporary/calls" PRE_PR_FAIL_TARGET=lint-python \
    MOCK_CHANGED_FILES=scripts/example.py \
    bash "$repository/scripts/make/pre-pr-checks.sh" > "$temporary/output" 2>&1 || status=$?
[ "$status" -eq 7 ]
grep -Fq 'make lint-python grouped=1 xdebug=coverage run_target=lint-python' "$PRE_PR_CALLS"
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
    run-tests-php phpmetrics docker-build-pre-pr-development docker-build-pre-pr-production; do
    grep -Eq "^make ${target} .*run_target=${target}$" "$PRE_PR_CALLS"
done
grep -Fqx 'make ci-detect-changes grouped= xdebug=coverage run_target=ci-detect-changes' "$PRE_PR_CALLS"
grep -Fq 'script ci-validate-compose' "$PRE_PR_CALLS"
grep -Fq 'script docker-images check-updates --advisory grouped=1' "$PRE_PR_CALLS"
if grep -q '^docker buildx build ' "$PRE_PR_CALLS"; then
    printf 'Pre-PR image builds must use their Make targets.\n' >&2
    exit 1
fi
if ! awk '
    $0 == "[OK] Build development Docker image (make docker-build-pre-pr-development)" {
        if (getline <= 0 || $0 != "") exit 1
        if (getline <= 0 || $0 != "[RUN] Build production Docker image (make docker-build-pre-pr-production)") exit 1
        found = 1
    }
    END { if (!found) exit 1 }
' "$temporary/output"; then
    printf 'Expected one blank line between image builds.\n' >&2
    exit 1
fi
if grep -Fq 'unwanted log' "$temporary/output"; then
    printf 'Pre-PR output included a suppressed deprecation log.\n' >&2
    exit 1
fi
[ "$(grep -c 'deprecation.WARNING: visible warning' "$temporary/output")" -eq 2 ]
[ "$(grep -c 'visible diagnostic' "$temporary/output")" -eq 2 ]
if ! awk '
    $0 == "  [OK] Major reporting stays separate from automatic pin updates" {
        if (getline <= 0 || $0 != "[OK] Docker image tests: 98 scenarios passed") exit 1
        if (getline <= 0 || $0 != "") exit 1
        if (getline <= 0 || $0 != "[RUN] Check Docker image updates") exit 1
        found = 1
    }
    END { if (!found) exit 1 }
' "$temporary/output"; then
    printf 'Incorrect spacing around Docker image check outcomes:\n' >&2
    cat "$temporary/output" >&2
    exit 1
fi

run_checks $'composer.json\nconfig/packages/framework.yaml'
if grep -Fq 'unwanted log' "$temporary/output"; then
    printf 'Selected pre-PR checks included a suppressed deprecation log.\n' >&2
    exit 1
fi
grep -Fq 'deprecation.WARNING: visible warning' "$temporary/output"

for failed in docker-build-pre-pr-development docker-build-pre-pr-production; do
    status=0
    : > "$PRE_PR_CALLS"
    PRE_PR_CALLS="$temporary/calls" PRE_PR_FAIL_TARGET="$failed" \
        MOCK_CHANGED_FILES=Dockerfile \
        bash "$repository/scripts/make/pre-pr-checks.sh" > "$temporary/output" 2>&1 || status=$?
    [ "$status" -eq 7 ]
    grep -Fq "make $failed grouped=1 xdebug=coverage run_target=$failed" "$PRE_PR_CALLS"
    if [ "$failed" = docker-build-pre-pr-development ] &&
        grep -Fq 'make docker-build-pre-pr-production ' "$PRE_PR_CALLS"; then
        printf 'Production build ran after the development build failed.\n' >&2
        exit 1
    fi
    if grep -Fq 'Selected local pre-PR checks completed.' "$temporary/output"; then
        printf 'A failed image build reported successful completion.\n' >&2
        exit 1
    fi
done

printf 'Pre-PR check selection tests passed.\n'
