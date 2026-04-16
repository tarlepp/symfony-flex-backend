# What is this?

This document explains how to maintain SHA-pinned GitHub Actions in this
project.

All actions are pinned to commit SHAs (not version tags) to reduce
supply-chain risk.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Overview](#overview)
  * [Current Action Pins](#current-action-pins)
  * [How to Update Actions](#how-to-update-actions)
  * [Checking for Updates](#checking-for-updates)
  * [Testing Updates](#testing-updates)
  * [Understanding SHA Pins](#understanding-sha-pins)

---

## Overview

This project uses SHA-pinned GitHub Actions instead of floating version tags.
That gives:

* **Security**: Prevents compromised tags from silently changing CI behavior.
* **Reproducibility**: The same action code runs every time.
* **Auditability**: It is easy to track exactly which code is running.
* **Maintenance cost**: Updates are manual and must be verified.

### Workflows with pinned actions

* `.github/workflows/main.yml` for linting, tests, and builds.
* `.github/workflows/codeql-analysis.yml` for CodeQL scanning.
* `.github/workflows/scorecard.yml` for supply-chain scoring.
* `.github/workflows/vulnerability-scan.yml` for vulnerability scanning.

---

## Current Action Pins

The current pin list is generated from `.github/workflows/*.yml` so it does not
become stale.

Run this command to print current pins as markdown:

```bash
bash scripts/check-action-updates.sh --current-pins-md
```

If you are on the host machine, run it in the dev container (`make bash`) or in
your Dev Container terminal.

---

## How to Update Actions

### Step 1: Identify the action to update

Check which version is currently pinned in workflow files. Example:

```yaml
uses: actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd # v6.0.2
```

The comment is the version tag. The SHA appears before the comment.

### Step 2: Get the new commit SHA

Use one of these methods to find the SHA for a new version.

#### Method A: `git ls-remote` (recommended)

```bash
# Get SHA for a specific version tag.
git ls-remote --tags https://github.com/actions/checkout.git \
  refs/tags/v6.0.3 | awk '{print $1}'
```

#### Method B: GitHub CLI

```bash
# If GitHub CLI is installed.
gh api repos/actions/checkout/git/refs/tags/v6.0.3 --jq '.object.sha'
```

#### Method C: `curl` + `jq`

```bash
# Get latest release metadata.
curl -s https://api.github.com/repos/actions/checkout/releases/latest \
  | jq '.target_commitish'
```

### Step 3: Update workflow files

Update every occurrence of that action across workflows.
Example: update `actions/checkout` from `v6.0.2` to `v6.0.3`.

Before:

```yaml
uses: actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd # v6.0.2
```

After:

```yaml
uses: actions/checkout@ABC123DEF456... # v6.0.3
```

Make sure to:

* Update all occurrences of that action.
* Keep the version tag in the comment.
* Use the full 40-character SHA.

### Step 4: Verify the update

Ensure all references were replaced:

```bash
# Search for old version.
grep -r "v6.0.2" .github/workflows/

# Verify new version.
grep -r "v6.0.3" .github/workflows/
```

### Step 5: Create a pull request

1. Create a branch: `git checkout -b chore/update-github-actions`
2. Commit updates with a clear message.
3. Push and open a PR.
4. Verify CI passes with new action versions.
5. Merge after approval.

---

## Checking for Updates

### Method 1: Dependabot notifications

Dependabot can suggest action updates, but you still need to verify and update
the SHA manually.

Dependabot updates version tags in references. It does not select and verify the
new commit SHA for you.

Typical flow:

1. Review the Dependabot PR suggestion.
2. Fetch the SHA for that version.
3. Update the SHA in workflow files.

### Method 2: Automated check script (recommended)

This repository includes an update-check script:

* Location: `scripts/check-action-updates.sh`

Run it with:

```bash
make check-action-updates
```

If you are already inside the dev container:

```bash
bash scripts/check-action-updates.sh
```

What it does:

* Discovers pinned actions dynamically from `.github/workflows/*.yml`.
* Compares each pin against the latest compatible release tag.
* Resolves the full commit SHA for discovered updates.
* Shows summary output and source lines for each finding.
* Reports discovery warnings such as unpinned refs.

Example output:

```text
================================================================================
                  GitHub Actions Update Checker
================================================================================

Checking 7 GitHub Actions for updates...

UPDATE AVAILABLE: actions/setup-node
   v6.3.0 -> v6.3.1
   SHA:   53b83947abc123def456...
   Short: 53b83947

================================================================================
                           Summary
================================================================================
Total actions checked:   7
Discovery warnings:      0
Up-to-date:              5
Updates available:       2
```

Exit codes:

* `0`: no discovery warnings and no updates.
* `1`: updates are available.
* `2`: discovery warnings found.

Use in CI:

```yaml
# .github/workflows/check-updates.yml
name: Check Action Updates

on:
  schedule:
    - cron: '0 9 * * 1'

jobs:
  check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd # v6.0.2
      - run: bash scripts/check-action-updates.sh
```

Requirements:

* `git`
* `timeout`
* `bash`

Important behavior:

* The script tracks updates in the same major version line by default.
* This avoids false positives from alternate tag streams in some repositories.
* The script continues checks even when discovery warnings are present.
* Move to a new major version manually after reviewing release notes.

Note: the script reports updates but does not apply them.

### Method 3: GitHub security alerts

GitHub can notify you about:

* Dependabot action update alerts.
* Security vulnerabilities in actions.
* Breaking changes in action releases.

Check: Repository Settings -> Code security and analysis -> Dependabot alerts.

---

## Testing Updates

### 1. Test in a feature branch

Always test action updates before merging:

```bash
git checkout -b test/action-update
# Make action updates.
git push origin test/action-update
# Create a PR and verify CI passes.
```

### 2. Verify action behavior

Review release notes for behavioral changes:

```text
Example: https://github.com/actions/checkout/releases/tag/v6.0.3
```

### 3. Monitor first run after merge

After merge:

* Watch the next CI run carefully.
* Check logs for new warnings or errors.
* Verify behavior still matches expectations.

---

## Understanding SHA Pins

### What is a SHA pin?

A SHA is a cryptographic fingerprint of a specific commit.

* Version tag (mutable): `actions/checkout@v6.0.2`
* Commit SHA (immutable):
  `actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd`

### Why use SHA pins?

Tags can be moved. SHAs cannot be changed once published. Pinning SHAs keeps
workflow behavior stable and auditable.

### How GitHub Actions resolves references

GitHub supports both forms:

* Tag: `uses: actions/checkout@v6.0.2`
* Full SHA: `uses: actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd`
* Short SHA: `uses: actions/checkout@de0fac2e`

---

## Best Practices

Do:

* Keep the version tag in comments for readability.
* Update all instances of the same action together.
* Test updates in a feature branch before merging.
* Review changelogs before updating.
* Prefer full 40-character SHAs.

Do not:

* Mix tags and SHAs for the same action.
* Update only some instances of an action.
* Merge updates without testing.
* Use SHAs from unverified sources.
* Ignore breaking changes.

---

## Troubleshooting

### "Action not found" error

If this appears after updating:

```text
Error: Can't find 'node_modules/...' from action
```

The SHA may be incorrect. Verify it:

```bash
git ls-remote --tags https://github.com/actions/setup-node.git \
  refs/tags/v6.3.0 | awk '{print $1}'
```

### Workflows fail with a new action

Diagnose:

1. Check the action changelog for breaking changes.
2. Read workflow logs for precise errors.
3. Reproduce locally if possible.
4. Check the GitHub Actions status page.

Then:

* Revert to the previous pin while investigating.
* Open an issue in the action repository if needed.
* Contact maintainers when appropriate.

### SHA not found in repository

Problem: fetched SHA does not exist.

Verify:

```bash
# Confirm repository and tag.
git ls-remote https://github.com/OWNER/REPO.git refs/tags/TAG_NAME

# Confirm tag exists.
git ls-remote https://github.com/OWNER/REPO.git | grep TAG_NAME
```

---

## Useful Resources

* [GitHub Actions Security Hardening](https://docs.github.com/en/actions/security-guides)
* [Dependabot Documentation](https://docs.github.com/en/code-security/dependabot)
* [SLSA Framework](https://slsa.dev/)
* [GitHub Action Marketplace](https://github.com/marketplace?type=actions)

---

## FAQ

Q: Why not use version tags directly?
A: Tags can be retagged or deleted. SHAs are immutable.

Q: Can SHAs be updated automatically?
A: You can automate detection, but verification should stay manual.

Q: What if a security patch is released?
A: Update the pin quickly after reviewing changelog and CI impact.

Q: How often should actions be updated?
A: Apply security fixes quickly and review regular updates monthly.

Q: Can I use short SHAs?
A: GitHub supports short SHAs, but full SHAs are recommended.

---

## Last Updated

* **Date**: 2026-04-09
* **Updated By**: GitHub Copilot
* **Next Review**: 2026-05-09

See also: `doc/README.md` and `.github/workflows/`.

---

[Back to resources index](README.md)

[Back to main README.md](../README.md)
