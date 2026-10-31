#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/command-output.sh"

for report in build/logs/clover.xml build/logs/junit.xml; do
    if [ ! -s "$report" ]; then
        output_status FAIL "Required test report is missing or empty: $report; run make run-tests first"
        exit 1
    fi
done

output_status WARN 'Writing local Sonar report copies under build/sonar; existing copies will be replaced'
mkdir -p build/sonar
temporary=$(mktemp -d build/sonar/.prepare-XXXXXX)
trap 'rm -rf -- "$temporary"' EXIT
sed 's|name="/app/|name="|g' build/logs/clover.xml > "$temporary/clover.xml"
sed 's|file="/app/|file="|g' build/logs/junit.xml > "$temporary/junit.xml"
mv -- "$temporary/clover.xml" build/sonar/clover.xml
mv -- "$temporary/junit.xml" build/sonar/junit.xml
bash scripts/report-output.sh \
    'Local reports: build/sonar/clover.xml and build/sonar/junit.xml; no upload was performed' \
    build/sonar/clover.xml build/sonar/junit.xml
