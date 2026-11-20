#!/bin/bash

#
# Provide the entry point for the read-only project statistics report.
#
# Usage:
#   Run through the project Make target or invoke this script directly.
#
# Details:
#   Delegates collection and formatting to the adjacent Python implementation,
#   preserving its exit status and command-line behavior.
#
# Callers:
#   Makefile:
#     - `project-stats` target.
#   GitHub Actions:
#     - `.github/workflows/main.yml` statistics job invokes the target through
#       `ci-statistics-summary.sh`.
#

set -euo pipefail

exec python3 "$(dirname "${BASH_SOURCE[0]}")/project-stats.py"
