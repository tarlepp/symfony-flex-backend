#!/usr/bin/env bash
set -euo pipefail

if [ "${GITHUB_ACTIONS:-}" = "true" ] && [ -z "${COLOR+x}" ]; then
    COLOR=always
fi
# shellcheck source=scripts/command-output.sh
source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"

categories=(
    markdown documentation
    runtime composer yaml compose tooling
    workflows security images php tests docker
)
declare -A selected_checks

enable_checks() {
    local category
    for category in "$@"; do
        selected_checks["$category"]=true
    done
}

classify_path() {
    local file=$1

    case "$file" in
        *.md)
            enable_checks markdown documentation
            ;;
        .markdownlint*|*/.markdownlint*|scripts/markdown-output.sh)
            enable_checks markdown documentation tooling
            ;;
        scripts/check-skills.py)
            enable_checks documentation
            ;;
        doc/*|LICENSE)
            enable_checks documentation
            ;;
        # These inputs can change any runner, command or job contract.
        Makefile|Dockerfile|.dockerignore|.github/workflows/main.yml|.github/actions/*|\
        scripts/ci-detect-changes.sh|scripts/make.sh|scripts/command-output.sh|\
        scripts/native-output.*|scripts/report-output.sh|scripts/command-progress.py|\
        scripts/compose-context.sh)
            enable_checks "${categories[@]}"
            ;;
        composer.json|composer.lock|symfony.lock)
            enable_checks composer runtime yaml php tests docker security
            ;;
        phpunit*.xml*|infection.json*|tools/05_infection/*|tools/06_php-coveralls/*|\
        tools/08_phpmetrics/*|scripts/prepare-sonar-reports.sh|sonar-project.properties)
            enable_checks php tests
            ;;
        scripts/ci-test-reports.py)
            enable_checks php tests workflows
            ;;
        ecs.php|.php-cs-fixer*|.phplint*|phpstan*|psalm*|phpinsights*|\
        .phpinsights*|phpmd*|.phan/*|rector.php)
            enable_checks php
            ;;
        tools/*/composer.json|tools/*/composer.lock)
            enable_checks php security
            ;;
        src/*|migrations/*|bin/*|public/*|templates/*|translations/*|secrets/*)
            enable_checks runtime php tests docker
            ;;
        config/*)
            enable_checks runtime php tests docker
            case "$file" in
                *.yaml|*.yml) enable_checks yaml ;;
            esac
            ;;
        tests/*)
            enable_checks php tests
            ;;
        *.php)
            enable_checks runtime php tests docker
            ;;
        .env*|docker-entrypoint*.sh|docker/php/*|docker/mariadb/*)
            enable_checks runtime php tests compose docker
            case "$file" in
                *Dockerfile*) enable_checks images ;;
            esac
            ;;
        compose*.yaml|docker/ci-compose.yaml)
            enable_checks runtime php tests compose docker
            ;;
        docker-sync.yml)
            enable_checks compose tooling
            ;;
        docker/*|.devcontainer/*)
            enable_checks compose docker tooling
            case "$file" in
                *Dockerfile*) enable_checks images ;;
            esac
            ;;
        scripts/ci-failure-diagnostics.sh)
            enable_checks workflows
            ;;
        scripts/ci-validate-compose.sh)
            enable_checks compose workflows
            ;;
        .github/workflows/*|.github/zizmor.yml|scripts/lint-workflows.sh|\
        scripts/check-composite-action-shell.py|scripts/tests/composite-action-shell-test.py|\
        scripts/ci-prerequisite-gate.sh|scripts/ci-result-gate.sh|scripts/ci-gate-common.sh|\
        scripts/check-composer-cache.sh|scripts/tests/ci-gates-test.py|\
        scripts/tests/analyzer-workflow-test.py|scripts/tests/composer-cache-test.py|\
        scripts/tests/docker-image-workflow-test.py)
            enable_checks workflows
            ;;
        scripts/docker-images.sh|scripts/tests/docker-images-test.sh)
            enable_checks images tooling
            ;;
        scripts/check-security.sh)
            enable_checks security tooling
            ;;
        scripts/*)
            enable_checks tooling
            ;;
        .github/*|.idea/*|.editorconfig|.gitattributes|.gitignore|\
        .pre-commit-config.yaml|.codeclimate.yml)
            enable_checks tooling
            ;;
        *)
            echo "Unclassified path: $file; retaining full validation"
            enable_checks "${categories[@]}"
            ;;
    esac

    # Tool locks include audit inputs even for report/test tools.
    case "$file" in
        tools/*/composer.json|tools/*/composer.lock) enable_checks security ;;
    esac
}

detect_changes() {
    local comparison changed_files deleted_files category file

    # Unknown/empty comparisons and manual runs retain full validation.
    if [ "$EVENT_NAME" = "workflow_dispatch" ]; then
        echo "Manual run: retaining full validation."
        return 0
    fi
    if [ -z "$BASE_SHA" ] || [ -z "$HEAD_SHA" ]; then
        echo "Comparison SHA unavailable: retaining full validation."
        return 0
    fi

    comparison="$BASE_SHA..$HEAD_SHA"
    if [ "$EVENT_NAME" = "pull_request" ]; then
        comparison="$BASE_SHA...$HEAD_SHA"
    fi

    if ! changed_files=$(git diff --no-renames --name-only "$comparison"); then
        echo "Changed-file comparison failed: retaining full validation."
        return 0
    fi
    if [ -z "$changed_files" ]; then
        echo "No changed files found: retaining full validation."
        return 0
    fi

    echo "Changed files:"
    echo "$changed_files"

    # Quoted Git paths (including embedded newlines) stay fail-safe.
    if grep -q '^"' <<< "$changed_files"; then
        echo "Quoted Git paths found: retaining full validation."
        return 0
    fi

    for category in "${categories[@]}"; do
        selected_checks["$category"]=false
    done
    while IFS= read -r file; do
        classify_path "$file"
    done <<< "$changed_files"

    # Deleted/renamed non-Markdown targets can break existing links.
    if deleted_files=$(git diff --no-renames --name-only --diff-filter=D "$comparison"); then
        if [ -n "$deleted_files" ] || grep -qE '^scripts/check-skills\.py$' <<< "$changed_files"; then
            enable_checks documentation
        fi
    else
        echo "Deleted-file comparison failed: selecting documentation checks."
        enable_checks documentation
    fi
}

write_outputs() {
    local category configuration_changed=false

    for category in runtime composer yaml compose tooling; do
        if [ "${selected_checks[$category]}" = "true" ]; then
            configuration_changed=true
        fi
    done

    selected_checks[configuration]=$configuration_changed
    printf '\nValidation selection (not a list of changed files):\n'
    for category in "${categories[@]}" configuration; do
        printf '%s=%s\n' "$category" "${selected_checks[$category]}" >> "$GITHUB_OUTPUT"
        if [ "${selected_checks[$category]}" = "true" ]; then
            printf '  %-15s %sselected%s\n' "$category" "$OUTPUT_GREEN" "$OUTPUT_RESET"
        else
            printf '  %-15s %snot selected%s\n' "$category" "$OUTPUT_YELLOW" "$OUTPUT_RESET"
        fi
    done
}

write_summary() {
    local category
    printf '## Validation selection\n\n'
    printf 'Selection is not a pass/fail result or a list of changed files.\n'
    printf 'Conservative fallbacks can select checks without matching changes.\n\n'
    printf '| Category | Selection |\n| --- | --- |\n'
    for category in "${categories[@]}" configuration; do
        if [ "${selected_checks[$category]}" = "true" ]; then
            printf '| %s | 🟢 Selected |\n' "$category"
        else
            printf '| %s | ⚪ Not selected |\n' "$category"
        fi
    done
}

enable_checks "${categories[@]}"
detect_changes
write_outputs
if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
    write_summary >> "$GITHUB_STEP_SUMMARY" || echo "::warning::Could not write validation selection summary." >&2
fi
