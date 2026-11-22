#!/usr/bin/env bash

#
# Run the selected CI analyzer and capture PHP Insights output for its summary.
#
# Usage:
#   From the repository root on the GitHub runner:
#     bash scripts/ci/ci-run-analyzer.sh
#
# Environment:
#   ANALYZER_TARGET selects the Make target; RUNNER_TEMP holds captured output.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/main.yml` static analyzer matrix.
#

set -eo pipefail

if [ "$ANALYZER_TARGET" = phpinsights ]; then
    COLOR=never NO_COLOR=1 NATIVE_COLOR=never bash scripts/make/make.sh "$ANALYZER_TARGET" | tee "$RUNNER_TEMP/phpinsights-output.txt"
else
    bash scripts/make/make.sh "$ANALYZER_TARGET"
fi
