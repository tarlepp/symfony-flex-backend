#!/usr/bin/env bash
set -euo pipefail

# shellcheck source=scripts/ci-gate-common.sh
source "$(dirname "${BASH_SOURCE[0]}")/ci-gate-common.sh"

summary_row() {
    local name=$1 result=$2 selected=$3 scope=$4
    local reason="Job completed with result: $result."

    if [ "$result" = "skipped" ]; then
        if [ "$EVENT_NAME" = "schedule" ]; then
            case "$name" in
                "Dependency security"|"Docker image updates")
                    reason="Unexpected skip: this periodic check is required. The merge gate rejects this result."
                    ;;
                *)
                    reason="Scheduled CI runs only dependency security and image-update checks."
                    ;;
            esac
        elif [ "$CHANGES" != "success" ]; then
            reason="Change detection did not succeed; this is not an approved path-based skip."
        elif [ "$selected" = "false" ]; then
            reason="Changed files do not affect $scope."
        else
            reason="Unexpected skip: validation was selected. The merge gate rejects this result."
        fi
    fi

    printf '| %s | %s | %s |\n' "$name" "$result" "$reason"
}

write_summary() {
    echo "## CI prerequisite results"
    echo
    echo "Skipped does not mean passed. The gate accepts only skips allowed by event and change selection."
    echo
    echo "| Check | Result | Explanation |"
    echo "| --- | --- | --- |"
    summary_row "Change detection" "$CHANGES" true ""
    summary_row "Configuration lint" "$LINT_CONFIGURATION" "$CONFIGURATION_CHANGED" "application configuration or tooling"
    summary_row "Documentation lint" "$LINT_DOCUMENTATION" "$DOCUMENTATION_CHANGED" "documentation"
    summary_row "Workflow validation" "$LINT_WORKFLOWS" "$WORKFLOWS_CHANGED" "workflow definitions or validators"
    summary_row "Dependency security" "$SECURITY_CHECK" "$SECURITY_CHANGED" "dependency audit inputs"
    summary_row "Docker image updates" "$DOCKER_UPDATES" "$IMAGES_CHANGED" "Docker image pins or their tooling"
    summary_row "Docker pin verification" "$DOCKER_PINS" "$IMAGES_CHANGED" "Docker image pins or their tooling"
    summary_row "Static analyzer matrix" "$STATIC_ANALYZERS" "$PHP_CHANGED" "PHP validation inputs"
    summary_row "Lightweight analyzers" "$LIGHTWEIGHT_ANALYZERS" "$PHP_CHANGED" "PHP validation inputs"
    summary_row "Gitleaks" "$GITLEAKS" true ""
}

echo "Event: $EVENT_NAME"
echo "Change detection result: $CHANGES"
echo "PHP Security Checker result: $SECURITY_CHECK"
echo "Lint configuration result: $LINT_CONFIGURATION"
echo "Lint documentation result: $LINT_DOCUMENTATION"
echo "Workflow validation result: $LINT_WORKFLOWS"
echo "Static analyzer matrix result: $STATIC_ANALYZERS"
echo "Lightweight analyzer result: $LIGHTWEIGHT_ANALYZERS"
echo "Gitleaks result: $GITLEAKS"
echo "Docker base image pin verification result: $DOCKER_PINS"
echo "Docker image version check result: $DOCKER_UPDATES"

if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
    write_summary >> "$GITHUB_STEP_SUMMARY" || echo "::warning::Could not write the CI prerequisite summary."
fi

if [ "$EVENT_NAME" = "schedule" ]; then
    expect_result "Change detection" skipped "$CHANGES"
    expect_result "Dependency security" success "$SECURITY_CHECK"
    expect_result "Docker image updates" success "$DOCKER_UPDATES"
    expect_result "Gitleaks" skipped "$GITLEAKS"
    CONFIGURATION_CHANGED=false
    DOCUMENTATION_CHANGED=false
    WORKFLOWS_CHANGED=false
    PHP_CHANGED=false
    IMAGES_CHANGED=false
else
    expect_result "Change detection" success "$CHANGES"
    expect_selected "Dependency security" "$SECURITY_CHANGED" "$SECURITY_CHECK"
    expect_selected "Docker image updates" "$IMAGES_CHANGED" "$DOCKER_UPDATES"
    expect_result "Gitleaks" success "$GITLEAKS"
fi

expect_selected "Configuration lint" "$CONFIGURATION_CHANGED" "$LINT_CONFIGURATION"
expect_selected "Documentation lint" "$DOCUMENTATION_CHANGED" "$LINT_DOCUMENTATION"
expect_selected "Workflow validation" "$WORKFLOWS_CHANGED" "$LINT_WORKFLOWS"
expect_selected "Static analyzer matrix" "$PHP_CHANGED" "$STATIC_ANALYZERS"
expect_selected "Lightweight analyzers" "$PHP_CHANGED" "$LIGHTWEIGHT_ANALYZERS"
expect_selected "Docker pin verification" "$IMAGES_CHANGED" "$DOCKER_PINS"
