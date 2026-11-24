#!/usr/bin/env bash

#
# Select authentication for a Docker image maintenance pull request.
#
# Usage:
#   From the repository root on the GitHub runner:
#     bash scripts/docker/select-maintenance-pr-auth.sh php-minor
#     bash scripts/docker/select-maintenance-pr-auth.sh image-update
#
# Details:
#   Reads APP_CLIENT_ID, APP_PRIVATE_KEY and UPDATE_TOKEN from the environment;
#   credentials are never passed as arguments or written to outputs. Writes the
#   selected method to GITHUB_OUTPUT and any applicable warning to the log or
#   GITHUB_STEP_SUMMARY. PHP minor PRs do not fall back to GITHUB_TOKEN.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml`.
#

set -e

if [ "$#" -ne 1 ] || { [ "$1" != php-minor ] && [ "$1" != image-update ]; }; then
    echo "Usage: $0 {php-minor|image-update}" >&2
    exit 2
fi

mode=$1
if [ -n "$APP_CLIENT_ID" ] && [ -n "$APP_PRIVATE_KEY" ]; then
    if [ "$mode" = php-minor ]; then
        echo 'method=app' >> "$GITHUB_OUTPUT"
    else
        echo 'app=true' >> "$GITHUB_OUTPUT"
    fi
elif [ -n "$APP_CLIENT_ID" ] || [ -n "$APP_PRIVATE_KEY" ]; then
    if [ "$mode" = php-minor ]; then
        echo 'No PHP migration PR: configure both Docker image update App credentials, or neither.' >> "$GITHUB_STEP_SUMMARY"
    else
        echo '::error::Configure both DOCKER_IMAGE_UPDATE_APP_CLIENT_ID and DOCKER_IMAGE_UPDATE_APP_PRIVATE_KEY, or neither.'
    fi
    exit 1
elif [ -n "$UPDATE_TOKEN" ]; then
    if [ "$mode" = php-minor ]; then
        echo 'method=pat' >> "$GITHUB_OUTPUT"
    else
        echo 'app=false' >> "$GITHUB_OUTPUT"
    fi
elif [ "$mode" = php-minor ]; then
    echo 'No PHP migration PR: configure the Docker image update App or PAT so that the PR triggers CI.' >> "$GITHUB_STEP_SUMMARY"
else
    echo 'app=false' >> "$GITHUB_OUTPUT"
    echo '::warning::Using GITHUB_TOKEN: generated PRs will not trigger CI. Configure the update App or PAT, or close and reopen the PR as a maintainer before merging.'
    echo 'Warning: GITHUB_TOKEN fallback does not trigger PR CI; maintainer action is required.' >> "$GITHUB_STEP_SUMMARY"
fi
