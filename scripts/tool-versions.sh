#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"

# Version queries do not need debugger connections; Xdebug stays in the inventory.
export XDEBUG_MODE=off

if [ "${1:-}" = --stack ]; then
    shift

    if [ "$#" -eq 0 ]; then
        printf 'Usage: bash scripts/tool-versions.sh --stack docker compose [options]\n' >&2
        exit 64
    fi

    printf '\n%sStack platform versions (Docker host and service containers)%s\n' \
        "$OUTPUT_BLUE" "$OUTPUT_RESET"
    docker version --format 'Docker client: {{.Client.Version}} / Engine: {{.Server.Version}}' |
        sed "s/^/  $OUTPUT_GREEN/;s/$/$OUTPUT_RESET/"
    "$@" version --short |
        sed "s/^/  ${OUTPUT_GREEN}Docker Compose: /;s/$/$OUTPUT_RESET/"

    running_services=$("$@" ps --services --status running)

    for service in nginx mariadb; do
        if ! grep -Fxq "$service" <<< "$running_services"; then
            printf '  %s%s: service not running%s\n' \
                "$OUTPUT_YELLOW" "$service" "$OUTPUT_RESET"
            continue
        fi

        case "$service" in
            nginx)
                "$@" exec -T nginx nginx -v 2>&1 |
                    sed "s/^/  $OUTPUT_GREEN/;s/$/$OUTPUT_RESET/"
                ;;
            mariadb)
                "$@" exec -T mariadb mariadbd --version |
                    sed "s/^/  $OUTPUT_GREEN/;s/$/$OUTPUT_RESET/"
                ;;
        esac
    done

    exit 0
fi

case "$*" in
    '') inventory=development ;;
    --production) inventory=production ;;
    *)
        printf 'Usage: bash scripts/tool-versions.sh [--production]\n' >&2
        exit 64
        ;;
esac

if [ ! -f /.dockerenv ]; then
    printf 'Run this inventory inside the PHP container, not on the Docker host.\n' >&2

    if [ "$inventory" = production ]; then
        printf '%s\n' \
            'For an already-running local production deployment, use:' \
            'docker compose -f compose.yaml -f compose.prod.yaml exec -T -w /app php bash scripts/tool-versions.sh --production' >&2
    else
        printf 'From the repository root on the Docker host, use: make tool-versions\n' >&2
    fi

    exit 1
fi

section() {
    printf '\n%s%s%s\n' "$OUTPUT_BLUE" "$1" "$OUTPUT_RESET"
}

subsection() {
    printf '  %s%s%s\n' "$OUTPUT_BLUE" "$1" "$OUTPUT_RESET"
}

installed_entries() {
    local indentation=$1 line

    while IFS= read -r line || [ -n "$line" ]; do
        [ -n "${line//[[:space:]]/}" ] || continue

        printf '%s%s%s%s\n' "$indentation" "$OUTPUT_GREEN" "$line" "$OUTPUT_RESET"
    done
}

version_line() {
    awk -v prefix="$1" -v color="$OUTPUT_GREEN" -v reset="$OUTPUT_RESET" \
        'NF && !printed { print "  " color prefix $0 reset; printed = 1 }'
}

main_version() {
    "$@" 2> >(version_diagnostics >&2) | version_line ''
}

version_diagnostics() {
    # Match diagnostic categories, not individual packages or deprecation text.
    sed -E \
        -e '/^\[[^]]+\] deprecation\.[[:upper:]]+:/d' \
        -e '/^\{.*"channel":[[:space:]]*"deprecation"[,}]/d' \
        -e '/^PHP version [0-9][^ ]* \(.*\)$/d' \
        -e '/^Run the "[^"]+" command to get more detailed diagnostics output\.$/d'
}

project_dependencies() {
    jq -r --arg color "$OUTPUT_GREEN" --arg reset "$OUTPUT_RESET" \
        --slurpfile manifest composer.json '
        .packages[]
        | select($manifest[0].require[.name] != null)
        | "  \($color)\(.name): \(.version) - \(.description | split("\n")[0])\($reset)"
    ' vendor/composer/installed.json
}

platform_versions() {
    local PRETTY_NAME
    source /etc/os-release

    printf 'OS: %s\n' "$PRETTY_NAME" | installed_entries '  '
    printf 'Architecture: %s\n' "$(uname -m)" | installed_entries '  '
    main_version php --version
    main_version php-fpm --version
    jq -er --slurpfile manifest composer.json '
        .packages[]
        | select(.name == "symfony/framework-bundle")
        | "Symfony: \(.version)"
    ' vendor/composer/installed.json | installed_entries '  '
}

optional_version() {
    local label=$1 command=$2
    shift 2

    if command -v "$command" >/dev/null 2>&1; then
        if [ "$command" = thefuck ]; then
            # thefuck writes its version to stderr rather than stdout.
            "$command" "$@" 2>&1 | version_line "$label: "
        else
            "$command" "$@" | version_line "$label: "
        fi
    else
        printf '  %s%s: not installed or not on PATH%s\n' \
            "$OUTPUT_YELLOW" "$label" "$OUTPUT_RESET"
    fi
}

php_tool_packages() {
    local manifest installed area

    for manifest in tools/*/composer.json; do
        [ -f "$manifest" ] || continue

        installed="${manifest%/composer.json}/vendor/composer/installed.json"

        case "${manifest%/composer.json}" in
            tools/02_phpstan) area='Static analysis - PHPStan' ;;
            tools/03_psalm) area='Static analysis - Psalm' ;;
            tools/04_symplify) area='Coding standards and formatting' ;;
            tools/05_infection) area='Mutation testing' ;;
            tools/06_php-coveralls) area='Coverage reporting' ;;
            tools/07_phpinsights) area='Code quality and metrics' ;;
            tools/08_phpmetrics) area='Metrics reporting' ;;
            tools/09_rector) area='Automated refactoring' ;;
            tools/10_composer) area='Dependency maintenance' ;;
            tools/11_phplint) area='PHP syntax linting' ;;
            tools/12_php-parallel-lint) area='Parallel PHP syntax linting' ;;
            *) area="${manifest%/composer.json}" ;;
        esac

        subsection "$area"

        if [ ! -f "$installed" ]; then
            printf '    %s%s: dependencies not installed%s\n' \
                "$OUTPUT_YELLOW" "${manifest%/composer.json}" "$OUTPUT_RESET"
            continue
        fi

        # Installed metadata avoids misleading CLI versions and version flags
        # that perform another action (for example OpenAPI's --version).
        jq -r --arg color "$OUTPUT_GREEN" --arg reset "$OUTPUT_RESET" \
            --slurpfile manifest "$manifest" '
            .packages[]
            | select(
                ($manifest[0].require[.name] != null or
                 $manifest[0]["require-dev"][.name] != null or
                 .name == "squizlabs/php_codesniffer" or
                 .name == "cmgmyr/phploc") and
                .name != "roave/security-advisories"
            )
            | "    \($color)\(.name): \(.version)\($reset)"
        ' "$installed"
    done
}

if [ "$inventory" = production ]; then
    section 'Production runtime inventory (current container)'
    section 'Platform versions'
    platform_versions

    section 'Loaded PHP extensions'
    php -m | installed_entries '  '

    section 'Project main dependencies'
    project_dependencies
    exit 0
fi

section 'Development environment tool inventory (current container)'

# Noninteractive Compose exec does not load the shell's nvm initialization.
if ! command -v node >/dev/null 2>&1 && [ -s "${NVM_DIR:-$HOME/.nvm}/nvm.sh" ]; then
    source "${NVM_DIR:-$HOME/.nvm}/nvm.sh"
fi

section 'Platform versions'
platform_versions
optional_version Node.js node --version
main_version python3 --version

section 'PHP runtime extensions and debugging'
php --version | sed -n '/Zend Engine/,$p' | installed_entries '  '
optional_version phpdbg phpdbg --version

section 'Project main dependencies'
project_dependencies

section 'Tooling'
{
    section 'PHP dependency management'
    # This report owns the colors; native ANSI obscures diagnostic categories.
    main_version composer --version --no-ansi

    section 'Installed PHP development packages'
    subsection 'Project packages (composer.json require-dev)'
    jq -r --arg color "$OUTPUT_GREEN" --arg reset "$OUTPUT_RESET" \
        --slurpfile manifest composer.json '
        .packages[]
        | select($manifest[0]["require-dev"][.name] != null)
        | "    \($color)\(.name): \(.version)\($reset)"
    ' vendor/composer/installed.json

    subsection 'Isolated tool packages (tools/*/composer.json)'
    php_tool_packages | sed 's/^/  /'

    section 'Python tooling'
    main_version python3 -m pip --version
    subsection 'Installed packages'
    python3 -m pip list --disable-pip-version-check --format=columns | installed_entries '    '
    optional_version thefuck thefuck --version

    section 'Node.js tooling'
    optional_version npm npm --version
    optional_version nvm nvm --version

    if command -v npm >/dev/null 2>&1; then
        subsection 'Global packages'
        npm list --global --depth=0 --offline | installed_entries '    '
    fi

    section 'Shell and supporting tools'
    optional_version Bash bash --version
    optional_version Make make --version
    optional_version Git git --version
    optional_version curl curl --version
    optional_version jq jq --version
    optional_version OpenSSL openssl version
    optional_version Fish fish --version
    optional_version fzf fzf --version

    section 'Optional security and workflow validators on PATH'
    optional_version local-php-security-checker local-php-security-checker --version
    optional_version actionlint actionlint --version
    optional_version ShellCheck shellcheck --version
    optional_version zizmor zizmor --version
    optional_version action-validator action-validator --version
} | sed '/./s/^/  /'
