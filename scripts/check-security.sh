#!/usr/bin/env bash

#
# Run dependency advisory checks in the PHP development container.
#
# Usage:
#   Invoke through the repository's security-check Make target.
#
# Details:
#   Validates Composer advisory constraints without changing the lock file,
#   then refreshes and runs the local checker when it is installed.
#
# Callers:
#   Makefile:
#     - `check-security` target.
#   GitHub Actions:
#     - `.github/workflows/main.yml` runs the target in its security check.
#

set -euo pipefail

# shellcheck source=scripts/command-output.sh
source "$(dirname "${BASH_SOURCE[0]}")/command-output.sh"

ansi_flag=--no-ansi
if [ -n "$OUTPUT_RESET" ]; then
    ansi_flag=--ansi
fi

output_run 'Security advisory dependency constraints' \
    composer update --dry-run --no-plugins roave/security-advisories "$ansi_flag"

if command -v local-php-security-checker > /dev/null 2>&1; then
    output_run 'Refresh security advisory cache' local-php-security-checker --update-cache
    output_run 'Installed dependency security audit' local-php-security-checker

    printf '\n' >&2
    output_status OK 'All dependency security checks completed'
else
    printf '\n' >&2
    output_status SKIP 'Installed dependency security audit: local-php-security-checker is not installed'
fi
