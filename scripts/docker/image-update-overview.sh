#!/usr/bin/env bash

#
# Compose the Docker image maintenance workflow's Actions job summary.
#
# Usage:
#   From the repository root on the GitHub runner:
#     bash scripts/docker/image-update-overview.sh
#
# Details:
#   Reads workflow step outcomes, PR details and readiness status from the
#   environment. Uses the shared workflow overview renderer and, when prepared,
#   the saved RUNNER_TEMP/docker-image-changes.md. Appends to GITHUB_STEP_SUMMARY.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml`.
#

set -e

{
  if [ "$PHP_READINESS_SUMMARY" = success ]; then
    printf 'PHP minor readiness evidence is in the **Check PHP minor upgrade readiness** job summary (job result: %s).\n\n' "$PHP_READINESS_RESULT"
  else
    printf '## PHP minor upgrade readiness\n\n> [!WARNING]\n> PHP minor readiness summary was not confirmed; routine image updates remain separate.\n\n**Result: Unavailable**\n\nReason: Readiness summary publication did not succeed (summary step: %s; readiness job: %s). No compatibility conclusion can be drawn; see the readiness job logs.\n\n' "${PHP_READINESS_SUMMARY:-unavailable}" "$PHP_READINESS_RESULT"
  fi
  bash scripts/ci/ci-workflow-overview.sh \
    "Core CI's Docker image version report lists major upgrades for manual review." \
    'Maintenance container' "$BOOTSTRAP" \
    'Helper regression tests' "$TESTS" \
    'Image pin updates' "$UPDATE" \
    'Pin verification' "$VERIFY" \
    'Composer synchronization' "$COMPOSER" \
    'Doctrine synchronization' "$DOCTRINE" \
    'CI MariaDB synchronization' "$CI_MARIADB" \
    'Change report preparation' "$DETAILS" \
    'Pull request action' "$PULL_REQUEST"

  printf "\n## Pull request\n\nBase branch: \`%s\`\n\n" "$BASE_BRANCH"
  if [ "$PULL_REQUEST" != success ]; then
    printf 'No confirmed pull request result (action outcome: %s).\n' "$PULL_REQUEST"
  elif [ -n "$PR_URL" ]; then
    printf 'Docker image update PR (%s): %s\n' "${PR_OPERATION:-unchanged}" "$PR_URL"
  elif [ -n "$PR_OPERATION" ] && [ "$PR_OPERATION" != none ]; then
    printf 'Pull request action reported: %s.\n' "$PR_OPERATION"
  else
    echo 'No pull request changes.'
  fi

  printf '\n'
  if [ "$DETAILS" = success ]; then
    cat "$RUNNER_TEMP/docker-image-changes.md"
  else
    echo 'No verified change report was prepared. See the step outcomes and logs above.'
  fi
} >> "$GITHUB_STEP_SUMMARY"
