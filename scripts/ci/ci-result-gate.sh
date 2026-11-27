#!/usr/bin/env bash

#
# Enforce core CI job outcomes and eligible external reporting.
#
# Usage:
#   Run in GitHub Actions with event/ref, selection flags, prerequisite gate
#   result, and job results provided by the workflow environment.
#
# Details:
#   Writes an optional summary; skipped selected checks and unexpected report
#   skips fail the gate.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/main.yml` final CI result job.
#

set -euo pipefail

# shellcheck source=scripts/ci/ci-gate-common.sh
source "$(dirname "${BASH_SOURCE[0]}")/ci-gate-common.sh"

summary_row() {
    local name=$1 result=$2 selected=$3 scope=$4
    local reason="Job completed with result: $result."

    if [ -z "$result" ] || { [ "$result" != "success" ] && [ "$result" != "failure" ] &&
        [ "$result" != "cancelled" ] && [ "$result" != "skipped" ]; }; then
        reason="Result is unavailable or unrecognized; inspect the job and gate logs."
    elif [ "$result" = "failure" ] || [ "$result" = "cancelled" ]; then
        reason="Job $result; inspect its logs and annotations."
    elif [ "$result" = "skipped" ]; then
        if [ "$EVENT_NAME" = "schedule" ] && [ "$name" = "Prerequisite gate" ]; then
            reason="Unexpected skip: the prerequisite gate is required. Inspect its job logs."
        elif [ "$EVENT_NAME" = "schedule" ]; then
            reason="Not part of scheduled dependency-security and image-update validation."
        elif [ "$CHANGES" != "success" ]; then
            reason="Change detection did not succeed; validation selection is unavailable."
        elif [ "$selected" = "false" ]; then
            reason="Changed files do not affect $scope."
        elif [ "$selected" != "true" ]; then
            reason="Validation selection is unavailable or invalid; inspect the change detection and gate logs."
        else
            reason="Unexpected skip: validation was selected. The merge gate rejects this result."
        fi
    elif [ "$selected" = "false" ]; then
        reason="Unexpected result: validation was not selected. Inspect change detection and job logs."
    elif [ "$selected" != "true" ]; then
        reason="Validation selection is unavailable or invalid; inspect the change detection and gate logs."
    fi

    printf '| %s | %s | %s |\n' "$name" "$result" "$reason"
}

queue_summary_row() {
    local row=$1 mode=$2 expected=$3 result=$4

    if [ "$mode" = independent ] ||
        { [ "$mode" = result ] && expect_result "summary" "$expected" "$result" 2>/dev/null; } ||
        { [ "$mode" = selected ] && expect_selected "summary" "$expected" "$result" 2>/dev/null; }; then
        other_rows+=("$row")
    else
        attention_rows+=("$row")
    fi
}

write_summary() {
    local -a attention_rows=() other_rows=()
    local change_expected=success php_selection=$PHP_CHANGED tests_selection=$TESTS_CHANGED
    local docker_selection=$DOCKER_CHANGED report_expected=skipped report_row row

    if [ "$EVENT_NAME" = "schedule" ]; then
        change_expected=skipped
        php_selection=false
        tests_selection=false
        docker_selection=false
    elif [ "$CHANGES" != "success" ]; then
        php_selection=unavailable
        tests_selection=unavailable
        docker_selection=unavailable
    fi

    if [ "$TESTS_CHANGED" = "true" ] && {
        [ "$EVENT_NAME" = "pull_request" ] ||
        { [ "$EVENT_NAME" = "push" ] && [ "$REF" = "refs/heads/master" ]; }
    }; then
        report_expected=success
    fi

    local change_selection=true
    [ "$EVENT_NAME" != "schedule" ] || change_selection=false
    queue_summary_row "$(summary_row "Change detection" "$CHANGES" "$change_selection" "")" result "$change_expected" "$CHANGES"
    queue_summary_row "$(summary_row "Prerequisite gate" "$CI_SUMMARY" true "")" result success "$CI_SUMMARY"
    queue_summary_row "$(summary_row "Static analyzer matrix" "$STATIC_ANALYZERS" "$php_selection" "PHP validation inputs")" selected "$php_selection" "$STATIC_ANALYZERS"
    queue_summary_row "$(summary_row "Lightweight analyzers" "$LIGHTWEIGHT_ANALYZERS" "$php_selection" "PHP validation inputs")" selected "$php_selection" "$LIGHTWEIGHT_ANALYZERS"
    queue_summary_row "$(summary_row "PHPUnit" "$TEST" "$tests_selection" "application tests")" selected "$tests_selection" "$TEST"
    queue_summary_row "$(summary_row "Docker builds" "$DOCKER_BUILD" "$docker_selection" "application images")" selected "$docker_selection" "$DOCKER_BUILD"
    queue_summary_row "$(summary_row "Trivy image scans" "$DOCKER_SCAN" "$docker_selection" "application images")" selected "$docker_selection" "$DOCKER_SCAN"

    if [ "$EVENT_NAME" = "schedule" ]; then
        queue_summary_row "| Project statistics | skipped | Not collected on scheduled runs. |" independent "" ""
    else
        queue_summary_row "| Project statistics | independent | Informational only; this gate does not wait for collection. See the Project statistics job summary for outcomes when available. |" independent "" ""
    fi

    if [ "$REPORT" = "success" ] && [ "$report_expected" = "skipped" ]; then
        report_row="| External reporting | success | Unexpected result: reporting was not expected. Inspect the reporting and gate logs. |"
    elif [ "$REPORT" != "skipped" ]; then
        report_row=$(summary_row "External reporting" "$REPORT" true "")
    elif [ "$EVENT_NAME" != "pull_request" ] && {
        [ "$EVENT_NAME" != "push" ] || [ "$REF" != "refs/heads/master" ];
    }; then
        report_row="| External reporting | skipped | Uploads run only on PRs and master pushes with selected, successful tests and prerequisites. |"
    elif [ "$TESTS_CHANGED" = "false" ] && [ "$CHANGES" = "success" ]; then
        report_row="| External reporting | skipped | Tests were not selected, so no coverage artifacts were produced. |"
    elif [ "$CHANGES" = "success" ] && [ "$TESTS_CHANGED" != "true" ]; then
        report_row="| External reporting | skipped | Test selection is unavailable or invalid; inspect the change detection and gate logs. |"
    elif [ "$TEST" != "success" ] || [ "$CI_SUMMARY" != "success" ]; then
        report_row="| External reporting | skipped | Tests or prerequisite checks did not succeed; uploads require both. |"
    else
        report_row="| External reporting | skipped | Unexpected skip: uploads were expected. The merge gate rejects this result. |"
    fi
    queue_summary_row "$report_row" result "$report_expected" "$REPORT"

    echo "## Core CI result"
    echo
    printf 'Event: %s\n' "$EVENT_NAME"
    echo
    echo "Start here for the final gate's inputs. Expand CI prerequisite and job details for individual checks and skip reasons."

    if ((${#attention_rows[@]})); then
        printf '⚠️ **Needs attention: %s result(s).** These appear first; inspect the named job logs and annotations.\n' "${#attention_rows[@]}"
    else
        echo '✅ Required gate inputs match expected results.'
    fi
    echo
    echo "| Check | Result | Explanation |"
    echo "| --- | --- | --- |"

    for row in "${attention_rows[@]}" "${other_rows[@]}"; do
        printf '%s\n' "$row"
    done
}

echo "Event: $EVENT_NAME"
echo "Change detection result: $CHANGES"
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
