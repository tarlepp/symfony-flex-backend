#!/usr/bin/env bash

#
# Run the Action update checker in the PHP container and publish its result.
#
# Usage:
#   From the repository root on the GitHub runner:
#     bash scripts/ci/ci-action-update-status.sh
#
# Details:
#   Writes current, updates available, or incomplete to GITHUB_OUTPUT. Available
#   updates are advisory; other checker failures retain their exit status.
#   The checker executes in the Compose PHP development container.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/action-updates.yml`.
#

set -e

status=0
docker compose -f compose.yaml -f docker/ci-compose.yaml exec -T php \
  bash scripts/checks/check-action-updates.sh || status=$?

case "$status" in
  0)
    echo "result=current" >> "$GITHUB_OUTPUT"
    ;;
  1)
    echo "result=updates available" >> "$GITHUB_OUTPUT"
    ;;
  *)
    echo "result=incomplete" >> "$GITHUB_OUTPUT"
    echo "::error::Action and workflow tool update checks failed (exit $status)."
    exit "$status"
    ;;
esac
