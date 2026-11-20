#!/usr/bin/env bash

#
# Run offline command-output and Make contract regression suites.
#
# Usage:
#   Run from the repository root with make test-command-output.
#
# Details:
#   Docker, registries, and uploaders are mocked; no application data is mutated
#   and no uploads occur. Aggregates eight focused suites and host/CI forwarding
#   regressions.
#
# Callers:
#   Makefile:
#     - `test-command-output` target.
#   GitHub Actions:
#     - `.github/workflows/main.yml` tooling regression job runs the target.
#

set -euo pipefail

cd "$(dirname "$0")/../.."
source scripts/output/command-output.sh
OUTPUT_RUN_GROUPED=1

output_status RUN 'Command output contracts (offline fixtures)'
output_status INFO 'Docker, registries and uploaders are mocked; no application mutation or upload is performed'

OUTPUT_STATUS_INDENT='  '
for suite in \
    scripts/output/tests/command-output-test.py \
    scripts/make/tests/make-launcher-test.py \
    scripts/make/tests/make-check-output-test.py \
    scripts/make/tests/make-quality-output-test.py \
    scripts/make/tests/make-lifecycle-output-test.py \
    scripts/make/tests/make-information-output-test.py \
    scripts/checks/tests/check-outcomes-test.py \
    scripts/checks/tests/lint-workflows-output-test.py; do
    suite_name=${suite##*/}
    suite_name=${suite_name%-test.py}
    output_run "$suite_name regression suite" \
        python3 scripts/tests/run-workflow-tests.py "$suite"
done
output_run 'Host and CI forwarding regression suite' \
    python3 scripts/output/native-output.py --indent bash scripts/make/tests/make-container-test.sh

printf '\n' >&2
OUTPUT_STATUS_INDENT='' output_status OK 'Command output contracts: 9 regression suites passed'
