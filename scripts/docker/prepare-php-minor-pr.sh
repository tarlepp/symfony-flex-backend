#!/usr/bin/env bash

#
# Prepare the PHP minor migration PR branch and body on the GitHub runner.
#
# Usage:
#   From the repository root in the PHP minor readiness workflow:
#     bash scripts/docker/prepare-php-minor-pr.sh
#
# Details:
#   Uses BASE_BRANCH, PHP_CANDIDATE, PHP_READINESS_REPORT, PHP_CLOSING_ISSUES,
#   GITHUB_OUTPUT and RUNNER_TEMP from the workflow environment. Reads the PR
#   template and writes the branch output and php-minor-pr.md.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml`.
#

set -e

branch_id=$(printf '%s' "$BASE_BRANCH" | sha256sum | cut -c1-16)
printf 'branch=chore(docker)/php-minor-%s\n' "$branch_id" >> "$GITHUB_OUTPUT"
sed \
  -e "s|^\* Describe the change$|* Update PHP to \`${PHP_CANDIDATE}\` and align the root and tool Composer baselines.|" \
  -e 's|^\* Explain why it is needed$|* Adopt the next compatible PHP minor without upgrading locked packages.|' \
  .github/pull_request_template.md > "$RUNNER_TEMP/php-minor-pr.md"
cat >> "$RUNNER_TEMP/php-minor-pr.md" << 'EOF'

## Automated readiness evidence

* `bash scripts/docker/docker-images.sh check-php-minor --prepare` passed:
  verified PHP pin, development build, Composer lockfile platform checks,
  and lockfile refresh and validation with unchanged package versions and references.
* Application tests and the production build were not run before opening
  this PR. Normal PR CI must pass before merging.
EOF
printf '\n%s\n' "$PHP_READINESS_REPORT" >> "$RUNNER_TEMP/php-minor-pr.md"
if [ -n "$PHP_CLOSING_ISSUES" ]; then
  printf '\n%s\n' "$PHP_CLOSING_ISSUES" >> "$RUNNER_TEMP/php-minor-pr.md"
fi
