#!/usr/bin/env bash
set -euo pipefail

# shellcheck source=scripts/ci-gate-common.sh
source "$(dirname "${BASH_SOURCE[0]}")/ci-gate-common.sh"

summary_row() {
    local name=$1 result=$2 selected=$3 scope=$4
    local reason="Job completed with result: $result."

    if [ "$result" = "skipped" ]; then
        if [ "$EVENT_NAME" = "schedule" ]; then
            reason="Not part of scheduled dependency-security and image-update validation."
        elif [ "$CHANGES" != "success" ]; then
            reason="Change detection did not succeed; validation selection is unavailable."
        elif [ "$selected" = "false" ]; then
            reason="Changed files do not affect $scope."
        else
            reason="Unexpected skip: validation was selected. The merge gate rejects this result."
        fi
    fi

    printf '| %s | %s | %s |\n' "$name" "$result" "$reason"
}

write_summary() {
    echo "## Final CI results"
    echo
    echo "See the CI prerequisite results summary for lint, security and image-check skip reasons."
    echo
    echo "| Check | Result | Explanation |"
    echo "| --- | --- | --- |"
    summary_row "Prerequisite gate" "$CI_SUMMARY" true ""
    summary_row "Static analyzer matrix" "$STATIC_ANALYZERS" "$PHP_CHANGED" "PHP validation inputs"
    summary_row "Lightweight analyzers" "$LIGHTWEIGHT_ANALYZERS" "$PHP_CHANGED" "PHP validation inputs"
    summary_row "PHPUnit" "$TEST" "$TESTS_CHANGED" "application tests"
    summary_row "Docker builds" "$DOCKER_BUILD" "$DOCKER_CHANGED" "application images"
    summary_row "Trivy image scans" "$DOCKER_SCAN" "$DOCKER_CHANGED" "application images"

    if [ "$REPORT" != "skipped" ]; then
        summary_row "External reporting" "$REPORT" true ""
    elif [ "$EVENT_NAME" != "pull_request" ] && {
        [ "$EVENT_NAME" != "push" ] || [ "$REF" != "refs/heads/master" ];
    }; then
        echo "| External reporting | skipped | Uploads run only on PRs and master pushes with selected, successful tests and prerequisites. |"
    elif [ "$TESTS_CHANGED" = "false" ] && [ "$CHANGES" = "success" ]; then
        echo "| External reporting | skipped | Tests were not selected, so no coverage artifacts were produced. |"
    elif [ "$TEST" != "success" ] || [ "$CI_SUMMARY" != "success" ]; then
        echo "| External reporting | skipped | Tests or prerequisite checks did not succeed; uploads require both. |"
    else
        echo "| External reporting | skipped | Unexpected skip: uploads were expected. The merge gate rejects this result. |"
    fi
}

echo "Event: $EVENT_NAME"
echo "Change detection result: $CHANGES (code changed: $CODE_CHANGED)"
echo "Prerequisite gate result: $CI_SUMMARY"
echo "Static analyzer matrix result: $STATIC_ANALYZERS"
echo "Lightweight analyzer result: $LIGHTWEIGHT_ANALYZERS"
echo "PHPUnit result: $TEST"
echo "Docker build matrix result: $DOCKER_BUILD"
echo "Docker scan matrix result: $DOCKER_SCAN"
echo "External reporting result: $REPORT"

if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
    write_summary >> "$GITHUB_STEP_SUMMARY" || echo "::warning::Could not write the final CI summary."
fi

if [ "$EVENT_NAME" = "schedule" ]; then
    expect_result "Change detection" skipped "$CHANGES"
    PHP_CHANGED=false
    TESTS_CHANGED=false
    DOCKER_CHANGED=false
else
    expect_result "Change detection" success "$CHANGES"
fi

report_expected=skipped
if [ "$TESTS_CHANGED" = "true" ] && {
    [ "$EVENT_NAME" = "pull_request" ] ||
    { [ "$EVENT_NAME" = "push" ] && [ "$REF" = "refs/heads/master" ]; }
}; then
    report_expected=success
fi

expect_result "Prerequisite gate" success "$CI_SUMMARY"
expect_selected "Static analyzer matrix" "$PHP_CHANGED" "$STATIC_ANALYZERS"
expect_selected "Lightweight analyzers" "$PHP_CHANGED" "$LIGHTWEIGHT_ANALYZERS"
expect_selected "PHPUnit" "$TESTS_CHANGED" "$TEST"
expect_selected "Docker builds" "$DOCKER_CHANGED" "$DOCKER_BUILD"
expect_selected "Trivy image scans" "$DOCKER_CHANGED" "$DOCKER_SCAN"
expect_result "External reporting" "$report_expected" "$REPORT"
