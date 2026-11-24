#!/usr/bin/env bash

#
# Select CI validation categories from the event's Git change range.
#
# Usage:
#   Run in GitHub Actions with EVENT_NAME, BASE_SHA, HEAD_SHA, and
#   GITHUB_OUTPUT set.
#
# Details:
#   Manual runs or uncertain comparisons retain full validation. Writes
#   selection flags and eligibility previews, not job results, plus an optional
#   step summary.
#
# Callers:
#   Makefile:
#     - `ci-detect-changes` target.
#   GitHub Actions:
#     - `.github/workflows/main.yml` change-detection job.
#

set -euo pipefail

if [ "${GITHUB_ACTIONS:-}" = "true" ] && [ -z "${COLOR+x}" ]; then
    COLOR=always
fi

# shellcheck source=scripts/output/command-output.sh
source "$(dirname "${BASH_SOURCE[0]}")/../output/command-output.sh"

categories=(
    markdown documentation
    runtime composer yaml compose tooling
    workflows security images php tests docker
)
declare -A selected_checks
declare -A check_reasons

declare -A reason_references
reason_list=('')

# Classification sets the reason consumed by enable_checks; the first reason wins.
current_reason=''

job_ids=()
declare -A job_labels job_states job_notes

declare -A category_labels=(
    [markdown]='Markdown lint'
    [documentation]='Documentation links and skill metadata'
    [runtime]='Application information and requirements'
    [composer]='Root Composer validation'
    [yaml]='Symfony YAML lint'
    [compose]='Compose and Dev Container configuration'
    [tooling]='Development tooling checks'
    [workflows]='GitHub workflow validation'
    [security]='PHP dependency security checks'
    [images]='Docker image pins and update checks'
    [php]='PHP static and lightweight analyzers'
    [tests]='PHPUnit and test reports'
    [docker]='Docker image builds and scans'
    [configuration]='Configuration job (any configuration check)'
)

enable_checks() {
    local category

    for category in "$@"; do
        selected_checks["$category"]=true

        if [ -z "${check_reasons[$category]:-}" ]; then
            check_reasons["$category"]=$current_reason
        fi
    done
}

retain_full_validation() {
    local category

    for category in "${categories[@]}"; do
        selected_checks["$category"]=true
        check_reasons["$category"]=$1
    done
}

classify_path() {
    local file=$1

    current_reason="Changed file: $file (example)"

    case "$file" in
        *.md)
            enable_checks markdown documentation
            ;;
        .markdownlint*|*/.markdownlint*|scripts/output/markdown-output.sh|\
        scripts/checks/markdown-prose-wrap.cjs|scripts/checks/tests/markdown-prose-wrap-test.cjs)
            enable_checks markdown documentation tooling
            ;;
        scripts/checks/check-skills.py)
            enable_checks documentation
            ;;
        doc/*|LICENSE)
            enable_checks documentation
            ;;
        # These inputs can change any runner, command or job contract.
        Makefile|Dockerfile|requirements.txt|.dockerignore|\
        .github/workflows/main.yml|.github/actions/*|\
        scripts/ci/ci-detect-changes.sh|scripts/ci/ci-validate-bootstrap.sh|\
        scripts/make/make.sh|scripts/output/command-output.sh|scripts/output/native-output.*|\
        scripts/output/report-output.sh|scripts/output/command-progress.py|\
        scripts/make/compose-context.sh)
            retain_full_validation "Shared input: $file; full validation"
            ;;
        composer.json|composer.lock|symfony.lock)
            enable_checks composer runtime yaml php tests docker security
            ;;
        phpunit*.xml*|infection.json*|\
        tools/05_infection/*|tools/06_php-coveralls/*|tools/08_phpmetrics/*|\
        scripts/reports/prepare-sonar-reports.sh|sonar-project.properties)
            enable_checks php tests
            ;;
        scripts/ci/ci-test-reports.py)
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
        scripts/ci/ci-failure-diagnostics.sh)
            enable_checks workflows
            ;;
        scripts/ci/ci-validate-compose.sh)
            enable_checks compose workflows
            ;;
        scripts/checks/lint-workflows.sh|scripts/tests/run-workflow-tests.py|\
        scripts/checks/download-workflow-validator.sh|\
        scripts/checks/check-composite-action-shell.py|\
        scripts/ci/ci-prerequisite-gate.sh|scripts/ci/ci-result-gate.sh|\
        scripts/ci/ci-gate-common.sh|scripts/ci/ci-workflow-overview.sh|\
        scripts/ci/ci-statistics-summary.sh|scripts/ci/tests/ci-gates-test.py|\
        scripts/docker/docker-image-digest-summary.py|scripts/docker/php-minor-readiness-report.py|\
        scripts/docker/php-minor-issues.py)
            enable_checks workflows tooling
            ;;
        .github/workflows/*|.github/actionlint.yaml|.github/zizmor.yml|\
        scripts/checks/tests/composite-action-shell-test.py|\
        scripts/ci/check-composer-cache.sh|\
        scripts/ci/tests/analyzer-workflow-test.py|scripts/ci/tests/composer-cache-test.py|\
        scripts/docker/tests/docker-image-workflow-test.py)
            enable_checks workflows
            ;;
        scripts/docker/docker-images.sh|scripts/docker/tests/docker-images-test.sh)
            enable_checks images tooling
            ;;
        scripts/checks/check-security.sh)
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
            retain_full_validation "Unclassified file: $file; full validation"
            ;;
    esac

    case "$file" in
        *.py) enable_checks tooling ;;
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
        retain_full_validation "Manual run; full validation"
        return 0
    fi

    if [ -z "$BASE_SHA" ] || [ -z "$HEAD_SHA" ]; then
        echo "Comparison SHA unavailable: retaining full validation."
        retain_full_validation "Comparison SHA unavailable; full validation"
        return 0
    fi

    comparison="$BASE_SHA..$HEAD_SHA"
    if [ "$EVENT_NAME" = "pull_request" ]; then
        comparison="$BASE_SHA...$HEAD_SHA"
    fi

    if ! changed_files=$(git diff --no-renames --name-only "$comparison"); then
        echo "Changed-file comparison failed: retaining full validation."
        retain_full_validation "Changed-file comparison failed; full validation"
        return 0
    fi

    if [ -z "$changed_files" ]; then
        echo "No changed files found: retaining full validation."
        retain_full_validation "No changed files found; full validation"
        return 0
    fi

    printf '\n%sChanged files%s\n' "$OUTPUT_BLUE" "$OUTPUT_RESET"

    while IFS= read -r file; do
        printf '  - %s\n' "$file"
    done <<< "$changed_files"

    # Quoted Git paths (including embedded newlines) stay fail-safe.
    if grep -q '^"' <<< "$changed_files"; then
        echo "Quoted Git paths found: retaining full validation."
        retain_full_validation "Quoted Git paths; full validation"
        return 0
    fi

    for category in "${categories[@]}"; do
        selected_checks["$category"]=false
        check_reasons["$category"]=''
    done

    while IFS= read -r file; do
        classify_path "$file"
    done <<< "$changed_files"

    # Deleted/renamed non-Markdown targets can break existing links.
    if deleted_files=$(git diff --no-renames --name-only --diff-filter=D "$comparison"); then
        if [ -n "$deleted_files" ] || grep -qE '^scripts/checks/check-skills\.py$' <<< "$changed_files"; then
            if [ -n "$deleted_files" ]; then
                current_reason='Deleted or renamed files may affect documentation links'
            else
                current_reason='Documentation link checker changed'
            fi

            enable_checks documentation
        fi
    else
        echo "Deleted-file comparison failed: selecting documentation checks."
        current_reason='Deleted-file comparison failed; documentation checks retained'
        enable_checks documentation
    fi
}

prepare_reason_references() {
    local category reason index

    for category in "${categories[@]}" configuration; do
        reason=${check_reasons[$category]:-No changed files matched this check}

        for ((index = 1; index < ${#reason_list[@]}; index++)); do
            if [ "${reason_list[$index]}" = "$reason" ]; then
                break
            fi
        done

        if [ "$index" -eq "${#reason_list[@]}" ]; then
            reason_list+=("$reason")
        fi

        reason_references["$category"]=$index
    done
}

add_job() {
    local id=$1 label=$2 state=$3 note=$4

    job_ids+=("$id")
    job_labels["$id"]=$label
    job_states["$id"]=$state
    job_notes["$id"]=$note
}

add_category_job() {
    local id=$1 label=$2 category=$3 note=$4 state='not enabled'

    if [ "$EVENT_NAME" != schedule ] && [ "${selected_checks[$category]}" = true ]; then
        state=eligible
    fi

    add_job "$id" "$label" "$state" "$note"
}

prepare_jobs() {
    local always=eligible
    local periodic_security='not enabled' periodic_images='not enabled'
    local reporting='not enabled' reporting_note='Requires tests and an eligible event'

    if [ "$EVENT_NAME" = schedule ]; then
        always='not enabled'
        periodic_security=eligible
        periodic_images=eligible
    else
        [ "${selected_checks[security]}" = false ] || periodic_security=eligible
        [ "${selected_checks[images]}" = false ] || periodic_images=eligible
    fi

    add_job changes 'Detect changed files' "$always" 'Core CI change detection'
    add_category_job lint-configuration 'Lint configuration' configuration \
        'Runtime, Composer, YAML, Compose and tooling steps depend on their flags'
    add_category_job lint-documentation 'Lint documentation files' documentation \
        'Markdown lint depends on the markdown flag'
    add_category_job lint-workflows 'Validate GitHub workflows' workflows \
        'Workflow and local action checks'
    add_job security-check 'PHP Security Checker' "$periodic_security" \
        'Scheduled runs also enable this job'
    add_job gitleaks 'Gitleaks scan' "$always" 'Independent of changed files'
    add_category_job docker-image-verify-pins 'Verify Docker base image pins' images \
        'Multi-platform pin verification'
    add_job docker-image-check-updates 'Check Docker image versions' "$periodic_images" \
        'Scheduled runs also enable this job'
    add_job statistics 'Project statistics' "$always" 'Informational on non-scheduled runs'
    add_category_job lightweight-analyzers 'Lightweight static analyzers' php \
        'CodeSniffer, ECS, PHPLint, Parallel Lint and PHPLoc'
    add_category_job static-analyzers 'Static analyzers' php \
        'Matrix: Psalm, PHPStan, PHP Insights'
    add_job ci-summary 'Validate CI prerequisites' conditional 'Runs unless cancelled'
    add_category_job test 'PHPUnit tests' tests 'Includes coverage and report generation'

    if [ "${selected_checks[tests]}" = true ]; then
        case "$EVENT_NAME" in
            pull_request)
                reporting=conditional
                reporting_note='Requires successful tests and prerequisite gate; uploads require tokens'
                ;;
            push)
                reporting=conditional
                reporting_note='Requires a master push, successful tests and prerequisite gate; uploads require tokens'
                ;;
        esac
    fi

    add_job report 'External reporting' "$reporting" "$reporting_note"
    add_category_job docker-build 'Build application Docker image' docker \
        'Matrix: development, production'
    add_category_job docker-scan 'Scan application Docker image with Trivy' docker \
        'Matrix: development, production; scans available archives after builds finish'
    add_job ci-result 'Validate CI result' conditional 'Runs unless cancelled'
}

write_outputs() {
    local category index configuration_changed=false configuration_inputs=''

    for category in runtime composer yaml compose tooling; do
        if [ "${selected_checks[$category]}" = "true" ]; then
            configuration_changed=true
            configuration_inputs+="${configuration_inputs:+, }$category"
        fi
    done

    selected_checks[configuration]=$configuration_changed
    if [ "$configuration_changed" = true ]; then
        check_reasons[configuration]="Enabled configuration categories: $configuration_inputs"
    else
        check_reasons[configuration]='No configuration categories enabled'
    fi

    prepare_reason_references

    printf '\n%sCI validation preview%s (not results or changed-file categories):\n' \
        "$OUTPUT_BLUE" "$OUTPUT_RESET"
    printf 'Enabled means eligible to run; event rules and prerequisites still apply.\n'
    printf 'Numbers refer to the reasons below.\n\n'

    for category in "${categories[@]}" configuration; do
        printf '%s=%s\n' "$category" "${selected_checks[$category]}" >> "$GITHUB_OUTPUT"

        if [ "${selected_checks[$category]}" = "true" ]; then
            printf '  %s%-11s%s  %s[%s]%s %s (%s)\n' "$OUTPUT_GREEN" enabled "$OUTPUT_RESET" \
                "$OUTPUT_BLUE" "${reason_references[$category]}" "$OUTPUT_RESET" \
                "${category_labels[$category]}" "$category"
        else
            printf '  %s%-11s%s  %s[%s]%s %s (%s)\n' "$OUTPUT_YELLOW" 'not enabled' "$OUTPUT_RESET" \
                "$OUTPUT_BLUE" "${reason_references[$category]}" "$OUTPUT_RESET" \
                "${category_labels[$category]}" "$category"
        fi
    done

    printf '\n%sReasons%s\n' "$OUTPUT_BLUE" "$OUTPUT_RESET"
    for ((index = 1; index < ${#reason_list[@]}; index++)); do
        printf '  %s[%s]%s %s\n' "$OUTPUT_BLUE" "$index" "$OUTPUT_RESET" "${reason_list[$index]}"
    done
}

write_job_preview() {
    local id state color count

    printf '\n%sCore CI jobs%s\n' "$OUTPUT_BLUE" "$OUTPUT_RESET"
    printf 'Eligibility assumes core CI starts; dependencies and cancellation still apply.\n'
    printf 'Conditional jobs depend on results or event details unavailable here.\n'

    for state in eligible conditional 'not enabled'; do
        case "$state" in
            eligible) color=$OUTPUT_GREEN ;;
            'not enabled') color=$OUTPUT_YELLOW ;;
            conditional) color=$OUTPUT_BLUE ;;
        esac

        printf '\n  %s%s%s\n' "$color" "$state" "$OUTPUT_RESET"
        count=0

        for id in "${job_ids[@]}"; do
            if [ "${job_states[$id]}" = "$state" ]; then
                printf '    - %s (%s)\n' "${job_labels[$id]}" "$id"
                count=$((count + 1))
            fi
        done

        if [ "$count" -eq 0 ]; then
            printf '    - None\n'
        fi
    done

    printf '\n'
    printf '  Static analyzers: Psalm, PHPStan, PHP Insights.\n'
    printf '  Docker build and scan: development and production; scans require target archives.\n'
    printf '  Configuration and documentation steps depend on their category flags.\n'
    printf '  External reporting requires eligible events, successful tests and gates; uploads require tokens.\n'
}

escape_summary_reason() {
    local text=$1

    text=${text//&/\&amp;}
    text=${text//</\&lt;}
    text=${text//>/\&gt;}
    text=${text//|/\&#124;}
    text=${text//\\/\&#92;}
    text=${text//\`/\&#96;}
    text=${text//\*/\&#42;}
    text=${text//_/\&#95;}
    text=${text//\[/\&#91;}
    text=${text//\]/\&#93;}
    text=${text//\~/\&#126;}

    printf '%s' "$text"
}

write_summary() {
    local category index reason

    printf 'Find **Core CI result** among the job summaries on this page for the final gate results. '
    printf 'For gate logs, select **Validate CI result** in the jobs list or graph. '
    printf 'A cancelled run may not reach the final gate.\n\n'
    printf '<details>\n<summary>CI validation preview (selection, not results)</summary>\n\n'
    printf 'Enabled means eligible to run; event rules and prerequisites still apply.\n'
    printf 'This is not a pass/fail result or a list of changed-file categories. '
    printf 'Conservative fallbacks can enable checks without matching changes.\n\n'
    printf '| Validation | Category | Preview | Reason |\n| --- | --- | --- | --- |\n'

    for category in "${categories[@]}" configuration; do
        if [ "${selected_checks[$category]}" = "true" ]; then
            printf "| %s | \`%s\` | 🟢 Enabled | [%s] |\n" \
                "${category_labels[$category]}" "$category" "${reason_references[$category]}"
        else
            printf "| %s | \`%s\` | ⚪ Not enabled | [%s] |\n" \
                "${category_labels[$category]}" "$category" "${reason_references[$category]}"
        fi
    done

    printf '\n**Reasons**\n\n'
    for ((index = 1; index < ${#reason_list[@]}; index++)); do
        reason=$(escape_summary_reason "${reason_list[$index]}")
        printf -- '- [%s] %s\n' "$index" "$reason"
    done

    printf '\n### Core CI jobs\n\n'
    printf 'Eligibility assumes core CI starts; dependencies and cancellation still apply. '
    printf 'Conditional jobs depend on results or event details unavailable here.\n\n'
    printf '| Job | Preview | Details |\n| --- | --- | --- |\n'

    for category in "${job_ids[@]}"; do
        printf "| %s (\`%s\`) | %s | %s |\n" \
            "${job_labels[$category]}" "$category" \
            "${job_states[$category]}" "${job_notes[$category]}"
    done
    printf '\n</details>\n'
}

detect_changes
write_outputs
prepare_jobs
write_job_preview

if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
    write_summary >> "$GITHUB_STEP_SUMMARY" || echo "::warning::Could not write validation selection summary." >&2
fi
