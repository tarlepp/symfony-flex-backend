#!/usr/bin/env bash
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
