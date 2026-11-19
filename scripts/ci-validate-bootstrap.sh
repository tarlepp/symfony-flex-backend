#!/usr/bin/env bash

# Validate the GitHub Actions PHP-bootstrap action inputs.
# Requires exact boolean strings for dependency installation, caching, and
# version reporting; maintenance builds must disable all three operations.

set -euo pipefail

validate_boolean() {
    case "$2" in
        true|false) ;;
        *)
            printf "::error::Bootstrap input %s must be exactly 'true' or 'false'.\n" "$1"
            exit 1
            ;;
    esac
}

validate_boolean install-dependencies "$INSTALL_DEPENDENCIES"
validate_boolean cache-dependencies "$CACHE_DEPENDENCIES"
validate_boolean print-versions "$PRINT_VERSIONS"

case "$BUILD_TARGET" in
    development) ;;
    maintenance)
        if [ "$INSTALL_DEPENDENCIES" != false ] ||
            [ "$CACHE_DEPENDENCIES" != false ] ||
            [ "$PRINT_VERSIONS" != false ]; then
            echo "::error::Maintenance requires install-dependencies, cache-dependencies and print-versions to be false."
            exit 1
        fi
        ;;
    *)
        echo "::error::Unsupported bootstrap build-target."
        exit 1
        ;;
esac
