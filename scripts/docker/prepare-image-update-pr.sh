#!/usr/bin/env bash

#
# Prepare Docker image maintenance PR details and saved workflow evidence.
#
# Usage:
#   From the repository root in the Docker image update workflow:
#     bash scripts/docker/prepare-image-update-pr.sh
#
# Details:
#   Uses BASE_BRANCH, PHP_READINESS_REPORT, PHP_READINESS_RESULT, workflow step
#   outcomes, GITHUB_OUTPUT and RUNNER_TEMP. Captures Git diffs before PR
#   creation and writes the branch output, digest report, saved changes and
#   PR body. Digest comparison can query image registries.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml`.
#

set -e

branch_id=$(printf '%s' "$BASE_BRANCH" | sha256sum | cut -c1-16)
echo "branch=chore(docker)/image-updates-${branch_id}" >> "$GITHUB_OUTPUT"

python3 scripts/docker/docker-image-digest-summary.py > "$RUNNER_TEMP/docker-image-digests.md"
stat=$(git diff --no-ext-diff --stat=160,120 -- Dockerfile 'docker/*/Dockerfile*' \
  composer.json composer.lock 'tools/*/composer.json' 'tools/*/composer.lock' config/packages/doctrine.yaml .github/workflows/main.yml)
# The PR action can restore the working tree; capture the verified changes first.
{
  printf '## Proposed changes\n\n'
  if [ -n "$stat" ]; then
    printf '%s\n%s\n%s\n\n' '```text' "$stat" '```'
    printf '### Image pins and Doctrine server version\n\n%s\n' '```diff'
    git diff --no-ext-diff --unified=0 -- Dockerfile 'docker/*/Dockerfile*' config/packages/doctrine.yaml .github/workflows/main.yml
    printf '%s\n' '```'
  else
    echo 'No eligible file changes.'
  fi
  printf '\n'
  cat "$RUNNER_TEMP/docker-image-digests.md"
} > "$RUNNER_TEMP/docker-image-changes.md"

{
  if [ -n "$stat" ]; then
    printf '\nChanged files (Composer lockfiles are listed when changed):\n\n%s\n%s\n%s\n\n' '```text' "$stat" '```'
    details=$(git diff --no-ext-diff --unified=0 -- Dockerfile 'docker/*/Dockerfile*' \
      composer.json 'tools/*/composer.json' config/packages/doctrine.yaml .github/workflows/main.yml)
    if [ -n "$details" ]; then
      printf 'Exact image, Composer configuration, and database configuration changes:\n\n%s\n%s\n%s\n' '```diff' "$details" '```'
    else
      printf 'Only Composer lockfiles changed; their full diffs are omitted.\n'
    fi
  else
    printf '\nNo eligible file changes were produced.\n'
  fi
} > "$RUNNER_TEMP/docker-image-pr-summary.md"

outcome() {
  case "$1" in
    success) printf 'passed' ;;
    failure) printf 'failed' ;;
    skipped) printf 'skipped' ;;
    cancelled) printf 'cancelled' ;;
    '') printf 'unavailable' ;;
    *) printf 'Invalid workflow step outcome: %s\n' "$1" >&2; return 1 ;;
  esac
}

tests_outcome=$(outcome "${TESTS_OUTCOME:-}")
update_outcome=$(outcome "${UPDATE_OUTCOME:-}")
verify_outcome=$(outcome "${VERIFY_OUTCOME:-}")
composer_outcome=$(outcome "${COMPOSER_OUTCOME:-}")
doctrine_outcome=$(outcome "${DOCTRINE_OUTCOME:-}")
ci_mariadb_outcome=$(outcome "${CI_MARIADB_OUTCOME:-}")

{
  printf '\n### Maintenance workflow checks\n\n'
  printf "* Docker image helper regression tests (offline), \`bash scripts/make/make.sh test-docker-images\`: **%s**.\n" "$tests_outcome"
  printf "* Image update and registry resolution, \`bash scripts/make/make.sh docker-image-update-pins\`: **%s**.\n" "$update_outcome"
  printf "* Multi-platform pin and tag parity verification, \`bash scripts/make/make.sh docker-image-verify-pins\`: **%s**.\n" "$verify_outcome"
  printf '* Composer PHP synchronization or unchanged-baseline and lockfile verification: **%s**.\n' "$composer_outcome"
  printf "  Workflow command: \`git show HEAD:Dockerfile | docker compose -f compose.yaml -f docker/ci-compose.yaml exec -T -e COMPOSER_HOME=/tmp/composer php bash scripts/sync-composer-php.sh\`.\n"
  printf "* Doctrine MariaDB server-version synchronization, \`bash scripts/docker/sync-doctrine-mariadb.sh\`: **%s**.\n" "$doctrine_outcome"
  printf "* CI MariaDB image-pin synchronization, \`bash scripts/docker/sync-ci-mariadb.sh\`: **%s**.\n" "$ci_mariadb_outcome"
  cat << 'EOF'

Synchronization may verify an already-aligned file without changing it. Composer
updates requirements, platform settings and lockfiles only when the PHP tag
changes; otherwise it verifies them and fails on drift. See the changed files
above for what actually changed.

Application tests and builds of the proposed images are not run by this
maintenance job. The normal PR CI determines which checks to run; their
results are not available from this report.
EOF
} > "$RUNNER_TEMP/docker-image-pr-validation.md"

while IFS= read -r line || [ -n "$line" ]; do
  case "$line" in
    '* Describe the change')
      printf '%s\n' '* Refresh eligible Docker image versions and digest pins, and synchronize related configuration where needed.'
      ;;
    '* Explain why it is needed')
      printf '%s\n' '* Keep image pins aligned with upstream while preserving major versions, variants and supported platforms.'
      cat "$RUNNER_TEMP/docker-image-pr-summary.md"
      ;;
    '## Validation')
      printf '%s\n' "$line"
      cat "$RUNNER_TEMP/docker-image-pr-validation.md"
      ;;
    *) printf '%s\n' "$line" ;;
  esac
done < .github/pull_request_template.md > "$RUNNER_TEMP/docker-image-pr.md"

{
cat << 'EOF'

## Automated update details

PHP is limited to patch updates. When its tag changes, the root and tool
Composer requirements, platform settings and lockfiles are synchronized
without upgrading packages. When the tag is unchanged, baseline or lockfile
drift fails the check for manual review. Doctrine's configured server version
and the CI MariaDB service pin are synchronized with the MariaDB Dockerfile,
including when its tag is unchanged.
EOF
if [ -n "$PHP_READINESS_REPORT" ]; then
  printf '\n%s\n' "$PHP_READINESS_REPORT"
else
  printf '\n## PHP minor upgrade readiness\n\n**Result: Unavailable**\n\nReason: No readiness evidence was received (readiness job: %s). No compatibility conclusion can be drawn; see the readiness job logs.\n' "$PHP_READINESS_RESULT"
fi
cat "$RUNNER_TEMP/docker-image-digests.md"
} >> "$RUNNER_TEMP/docker-image-pr.md"
