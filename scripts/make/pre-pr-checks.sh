#!/usr/bin/env bash

#
# Run local CI checks selected from a pull-request commit range.
#
# Usage:
#   make pre-pr-checks
#   make pre-pr-checks-all
#   ./scripts/make/pre-pr-checks.sh [--all]
#
# Inputs:
#   BASE_SHA and HEAD_SHA select the committed change range. Defaults are
#   HEAD^ and HEAD; uncommitted changes are not included.
#
# Details:
#   Reuses scripts/ci/ci-detect-changes.sh. The --all option selects every local
#   check regardless of changed files. Hosted-only checks are not reproduced.
#
# Requirements:
#   Run on the Docker host with the development services available. Selected
#   checks may require Docker Buildx, database services, or registry access.
#
# Effects:
#   Selected tests and analyzers can overwrite their usual reports. The --all
#   option always builds the pre-pr-development and pre-pr-production tags;
#   selected mode builds them when the Docker category is selected.
#
# Callers:
#   Makefile:
#     - `pre-pr-checks` and `pre-pr-checks-all` targets run this script on host.
#

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/../.."
source scripts/output/command-output.sh

mode=selected

case "$#" in
    0) ;;
    1)
        if [ "$1" = "--all" ]; then
            mode=all
        else
            printf 'Usage: %s [--all]\n' "$0" >&2
            exit 64
        fi
        ;;
    *)
        printf 'Usage: %s [--all]\n' "$0" >&2
        exit 64
        ;;
esac

# The target is host-only; do not let Make's dry-run execute Docker or tests.
read -r -a make_flags <<< "${MAKEFLAGS:-}"
make_dry_run=false

for make_flag in "${make_flags[@]}"; do
    case "$make_flag" in
        --*) ;;
        -*)
            if [[ "${make_flag#-}" == *n* ]]; then
                make_dry_run=true
            fi
            ;;
        *)
            if [[ "$make_flag" == *n* ]]; then
                make_dry_run=true
            fi
            ;;
    esac
done

if [ "$make_dry_run" = true ]; then
    printf 'Skipping pre-PR checks in Make dry-run mode.\n'
    exit 0
fi

selection_file=$(mktemp "$PWD/.pre-pr-checks.XXXXXX")
selection_output="/app/${selection_file#"$PWD/"}"
trap 'rm -f -- "$selection_file"' EXIT

if [ "$mode" = all ]; then
    event_name=workflow_dispatch
else
    event_name=pull_request
fi

EVENT_NAME=$event_name \
    BASE_SHA="${BASE_SHA:-HEAD^}" \
    HEAD_SHA="${HEAD_SHA:-HEAD}" \
    GITHUB_OUTPUT="$selection_output" \
    OUTPUT_RUN_TARGET=ci-detect-changes \
    make ci-detect-changes

categories=(
    markdown documentation
    runtime composer yaml compose tooling
    workflows security images php tests docker
)

for category in "${categories[@]}"; do
    if ! grep -Eq "^${category}=(true|false)$" "$selection_file"; then
        printf 'Change selector did not produce a valid %s flag.\n' "$category" >&2
        exit 1
    fi
done

selected() {
    grep -Fxq "$1=true" "$selection_file"
}

run_make() {
    local target=$1

    printf '\n' >&2
    OUTPUT_RUN_GROUPED=1 OUTPUT_RUN_TARGET="$target" \
        python3 scripts/output/native-output.py --suppress-deprecation-info \
        make "$target" || exit "$?"
}

run_host_make() {
    local target=$1

    printf '\n' >&2
    OUTPUT_RUN_GROUPED=1 OUTPUT_RUN_TARGET="$target" make "$target" || exit "$?"
}

run_host_command() {
    local label=$1
    shift

    printf '\n' >&2
    OUTPUT_RUN_GROUPED=1 OUTPUT_RUN_GROUPED_SEPARATOR=0 output_run "$label" \
        python3 scripts/output/native-output.py --indent "$@" || exit "$?"
}

run_host_regression_suite() {
    local label=$1
    shift

    printf '\n' >&2
    OUTPUT_RUN_GROUPED=1 OUTPUT_RUN_GROUPED_SEPARATOR=0 output_run "$label" \
        "${compose[@]}" exec -T php env -u OUTPUT_RUN_GROUPED \
        python3 scripts/tests/run-workflow-tests.py "$@" || exit "$?"
}

compose=(docker compose)

if [ -n "${GITHUB_WORKFLOW:-}" ]; then
    compose+=(-f compose.yaml -f docker/ci-compose.yaml)
fi

if selected runtime; then
    run_make application-info
    run_make check-requirements
fi

if selected composer; then
    run_make composer-validate
fi

if selected yaml; then
    run_make lint-yaml
fi

if selected tooling; then
    run_make test-command-output
    run_host_regression_suite 'Workflow tool update regressions' \
        scripts/checks/tests/check-workflow-tool-updates-test.py
    run_make lint-python
    run_make typecheck-python
fi

if selected compose; then
    run_host_command 'Validate Compose configuration' bash scripts/ci/ci-validate-compose.sh
fi

if selected markdown; then
    run_make lint-markdown
fi

if selected documentation; then
    run_make check-skills
fi

if selected workflows; then
    run_make lint-workflows
fi

if selected security; then
    run_make check-security
fi

if selected images; then
    run_make test-docker-images
    run_host_command 'Check Docker image updates' \
        bash scripts/docker/docker-images.sh check-updates --advisory
    run_make docker-image-verify-pins
fi

if selected php; then
    run_make phpcs
    run_make ecs
    run_make phplint
    run_make php-parallel-lint
    run_make phploc
    run_make psalm
    run_make phpstan
    run_make phpinsights
fi

if selected tests; then
    run_make run-tests-php
    run_host_command 'Validate JUnit test report' "${compose[@]}" exec -T php \
        python3 scripts/ci/ci-test-reports.py validate-junit
    run_make phpmetrics
    run_host_command 'Verify generated test reports' "${compose[@]}" exec -T php \
        python3 scripts/ci/ci-test-reports.py verify
fi


if selected docker; then
    run_host_make docker-build-pre-pr-development
    run_host_make docker-build-pre-pr-production
fi

printf '\nSelected local pre-PR checks completed.\n'
