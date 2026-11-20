#!/usr/bin/env bash

#
# Validate generated report files before announcing their availability.
#
# Usage:
#   <message> <report-file> [report-file...].
#
# Details:
#   Every path must exist and be nonempty. Reports local outputs only; performs
#   no upload.
#
# Callers:
#   Makefile:
#     - The `REPORT_PATHS` helper used by test, Psalm, PHPLoc, and PhpMetrics
#       report targets.
#   Scripts:
#     - `scripts/prepare-sonar-reports.sh` verifies the generated copies.
#

set -euo pipefail

source "$(dirname "$0")/command-output.sh"

if [ "$#" -lt 2 ]; then
    output_status FAIL 'Usage: report-output.sh <message> <report-file> [report-file...]'
    exit 64
fi

message=$1
shift

for report in "$@"; do
    if [ ! -s "$report" ]; then
        output_status FAIL "Required generated report is missing or empty: $report"
        exit 1
    fi
done

output_status INFO "$message"
