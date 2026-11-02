#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"
output_run 'Security advisory dependency constraints' \
    composer update --dry-run --no-plugins roave/security-advisories

if command -v local-php-security-checker > /dev/null 2>&1; then
    output_run 'Refresh security advisory cache' local-php-security-checker --update-cache
    output_run 'Installed dependency security audit' local-php-security-checker
    printf '\n' >&2
    output_status OK 'All dependency security checks completed'
else
    printf '\n' >&2
    output_status SKIP 'Installed dependency security audit: local-php-security-checker is not installed'
fi
