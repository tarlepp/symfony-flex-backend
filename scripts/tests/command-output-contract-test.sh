#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."
source scripts/command-output.sh

output_status RUN 'Command output contracts (offline fixtures)'
output_status INFO 'Docker, registries and uploaders are mocked; no application mutation or upload is performed'

for suite in command-output make-launcher make-check-output make-quality-output \
    make-lifecycle-output make-information-output check-outcomes lint-workflows-output; do
    output_run "$suite regression suite" python3 "scripts/tests/$suite-test.py"
done
output_run 'Host and CI forwarding regression suite' bash scripts/tests/make-container-test.sh

printf '\n' >&2
output_status OK 'Command output contracts: 9 regression suites passed'
