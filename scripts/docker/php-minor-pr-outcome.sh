#!/usr/bin/env bash

#
# Publish the PHP minor migration PR outcome to the Actions job summary.
#
# Usage:
#   From the repository root on the GitHub runner:
#     bash scripts/docker/php-minor-pr-outcome.sh
#
# Details:
#   Reads PR_OUTCOME and PR_URL from the workflow and appends the result to
#   GITHUB_STEP_SUMMARY. The calling step is informational.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml`.
#

set -e

if [ "$PR_OUTCOME" = success ] && [ -n "$PR_URL" ]; then
  printf '\nPHP minor migration PR: %s\n' "$PR_URL" >> "$GITHUB_STEP_SUMMARY"
elif [ "$PR_OUTCOME" = success ]; then
  printf '\nNo PHP minor PR changes.\n' >> "$GITHUB_STEP_SUMMARY"
else
  printf '\nNo PHP minor PR was confirmed (PR step: %s).\n' "$PR_OUTCOME" >> "$GITHUB_STEP_SUMMARY"
fi
