#!/usr/bin/env bash

# Run the offline command-output and Make contract regression suites.
# Docker, registries, and uploaders are mocked; this does not mutate application
# data or perform uploads. Run from the repository root with Make target
# test-command-output; the fixture aggregates eight Python suites and the
# separate host/CI forwarding regression.

set -euo pipefail

cd "$(dirname "$0")/../.."
source scripts/command-output.sh
OUTPUT_RUN_GROUPED=1

output_status RUN 'Command output contracts (offline fixtures)'
output_status INFO 'Docker, registries and uploaders are mocked; no application mutation or upload is performed'

OUTPUT_STATUS_INDENT='  '
for suite in command-output make-launcher make-check-output make-quality-output \
    make-lifecycle-output make-information-output check-outcomes lint-workflows-output; do
    output_run "$suite regression suite" \
        python3 scripts/run-workflow-tests.py "scripts/tests/$suite-test.py"
done
output_run 'Host and CI forwarding regression suite' \
    python3 scripts/native-output.py --indent bash scripts/tests/make-container-test.sh

printf '\n' >&2
OUTPUT_STATUS_INDENT='' output_status OK 'Command output contracts: 9 regression suites passed'
