#!/usr/bin/env bash

#
# Prepare Docker image maintenance PR details and saved workflow evidence.
#
# Usage:
#   From the repository root in the Docker image update workflow:
#     bash scripts/docker/prepare-image-update-pr.sh
#
# Details:
#   Uses BASE_BRANCH, PHP_READINESS_REPORT, PHP_READINESS_RESULT, GITHUB_OUTPUT
#   and RUNNER_TEMP from the workflow environment. Captures Git diffs before
#   PR creation and writes the branch output, digest report, saved changes and
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
    echo 'No eligible tag or digest changes.'
  fi
  printf '\n'
  cat "$RUNNER_TEMP/docker-image-digests.md"
} > "$RUNNER_TEMP/docker-image-changes.md"

sed \
  -e 's|^\* Describe the change$|* Refresh Docker image pins with available stable minor/patch versions or digest-only updates, without changing their major versions; floating tags receive digest refreshes only.|' \
  -e 's|^\* Explain why it is needed$|* Keep image pins aligned with upstream while preserving variants and supported platforms. Major-version upgrades remain a manual review.|' \
  .github/pull_request_template.md > "$RUNNER_TEMP/docker-image-pr.md"

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
cat << 'EOF'

## Dockerfile and CI image changes

The diff lists each changed Dockerfile and the exact old and new image
references, including the CI MariaDB service:

```diff
EOF
git diff --no-ext-diff --unified=0 -- Dockerfile 'docker/*/Dockerfile*' .github/workflows/main.yml
cat << 'EOF'
```

## Automated validation evidence

* `bash scripts/make/make.sh test-docker-images` — passed (offline regression tests).
* `bash scripts/make/make.sh docker-image-update-pins` — passed (live registry resolution).
* `bash scripts/make/make.sh docker-image-verify-pins` — passed (live pin verification).
* Composer PHP synchronization or unchanged-baseline verification and lockfile validation passed.
* Doctrine server version synchronization passed.
* CI MariaDB service pin synchronization passed.
* Application tests and builds are not run by this Docker maintenance workflow.
EOF
} >> "$RUNNER_TEMP/docker-image-pr.md"
