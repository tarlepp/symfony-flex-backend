# What is this?

<a id="what-is-this"></a>

This document explains how to maintain SHA-pinned GitHub Actions in this
project.

All actions are pinned to commit SHAs (not version tags) to reduce
supply-chain risk.

For AI-assisted updates, follow the
[GitHub Actions update skill](../.github/skills/update-github-actions/SKILL.md).
For CI jobs, triggers, and shared bootstrap behavior, use the
[workflow maintenance skill](../.github/skills/update-github-workflows/SKILL.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Overview](#overview)
      * [Workflows with pinned actions](#workflows-with-pinned-actions)
    * [Current Action Pins](#current-action-pins)
    * [How to Update Actions](#how-to-update-actions)
      * [Step 1: Identify the action to update](#step-1-identify-the-action-to-update)
      * [Step 2: Get the new commit SHA](#step-2-get-the-new-commit-sha)
        * [Method A: `git ls-remote` (recommended)](#method-a-git-ls-remote-recommended)
        * [Method B: GitHub CLI](#method-b-github-cli)
        * [Method C: `curl` + `jq`](#method-c-curl-jq)
      * [Step 3: Update workflow files](#step-3-update-workflow-files)
      * [Step 4: Verify the update](#step-4-verify-the-update)
      * [Step 5: Create a pull request](#step-5-create-a-pull-request)
    * [Checking for Updates](#checking-for-updates)
      * [Method 1: Dependabot notifications](#method-1-dependabot-notifications)
      * [Method 2: Automated check script (recommended)](#method-2-automated-check-script-recommended)
      * [Method 3: GitHub security alerts](#method-3-github-security-alerts)
    * [Testing Updates](#testing-updates)
      * [1. Test in a feature branch](#1-test-in-a-feature-branch)
      * [2. Verify action behavior](#2-verify-action-behavior)
      * [3. Monitor first run after merge](#3-monitor-first-run-after-merge)
    * [Understanding SHA Pins](#understanding-sha-pins)
      * [What is a SHA pin?](#what-is-a-sha-pin)
      * [Why use SHA pins?](#why-use-sha-pins)
      * [How GitHub Actions resolves references](#how-github-actions-resolves-references)
    * [Best Practices](#best-practices)
    * [Troubleshooting](#troubleshooting)
      * ["Action not found" error](#action-not-found-error)
      * [Workflows fail with a new action](#workflows-fail-with-a-new-action)
      * [SHA not found in repository](#sha-not-found-in-repository)
    * [Useful Resources](#useful-resources)
    * [FAQ](#faq)

## Overview [ᐞ](#table-of-contents)

<a id="overview"></a>

This project uses SHA-pinned GitHub Actions instead of floating version tags.
That gives:

* **Security**: Prevents compromised tags from silently changing CI behavior.
* **Reproducibility**: The same action code runs every time.
* **Auditability**: It is easy to track exactly which code is running.
* **Maintenance cost**: Updates are manual and must be verified.

### Workflows with pinned actions [ᐞ](#table-of-contents)

<a id="workflows-with-pinned-actions"></a>

* `.github/workflows/main.yml` for linting, tests, and builds.
* `.github/workflows/dependency-review.yml` for dependency vulnerability
  review on pull requests.
* `.github/workflows/scorecard.yml` for supply-chain scoring.
* `.github/workflows/vulnerability-scan.yml` for vulnerability scanning, with
  Trivy results also uploaded as SARIF to code scanning.
* `.github/workflows/codeql.yml` for CodeQL analysis of the workflow files.

---

## Current Action Pins [ᐞ](#table-of-contents)

<a id="current-action-pins"></a>

The current pin list is generated from `.github/workflows/*.yml` and
`.github/actions/*/action.yml`, plus the `tool-pin` declarations in
`scripts/lint-workflows.sh`, so it does not become stale.

Run this command to print current pins as markdown:

```bash
bash scripts/check-action-updates.sh --current-pins-md
```

If you are on the host machine, run it in the dev container (`make bash`) or in
your Dev Container terminal.

The standalone workflow validators are release binaries, not Actions.
Their declarations pair an exact release tag with its verified source commit
SHA; platform-specific SHA-256 checksums separately pin the downloaded bytes.
The normal update check includes these tools. To check only workflow tools:

```bash
bash scripts/check-action-updates.sh --tools-only
```

Tool checks verify tag/commit parity, prefer peeled commits for annotated tags,
and report the newest full stable version tag and commit, excluding prerelease
and floating tags. Missing pins, lookup failures, and moved/missing tags fail
with exit code `2`; updates return `1`. This stricter parity/error behavior is
specific to tool pins; the existing Action lookup limitations still apply.
Review compatibility and update the release tag, commit comment, and both
platforms' binary checksums together. Do not substitute a commit SHA for a
binary checksum or automatically install the suggested version.

---

## How to Update Actions [ᐞ](#table-of-contents)

<a id="how-to-update-actions"></a>

### Step 1: Identify the action to update [ᐞ](#table-of-contents)

<a id="step-1-identify-the-action-to-update"></a>

Check which version is currently pinned in workflow files. Example:

```yaml
uses: actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd # v6.0.2
```

The comment is the version tag. The SHA appears before the comment.

### Step 2: Get the new commit SHA [ᐞ](#table-of-contents)

<a id="step-2-get-the-new-commit-sha"></a>

Use one of these methods to find the SHA for a new version.

#### Method A: `git ls-remote` (recommended) [ᐞ](#table-of-contents)

<a id="method-a-git-ls-remote-recommended"></a>

```bash
# Get SHA for a specific version tag.
git ls-remote --tags https://github.com/actions/checkout.git \
  'refs/tags/v6.0.3' 'refs/tags/v6.0.3^{}'
```

Use the peeled `^{}` commit for an annotated tag; the unpeeled value identifies
the tag object. For a lightweight tag, verify that its target is a commit.

#### Method B: GitHub CLI [ᐞ](#table-of-contents)

<a id="method-b-github-cli"></a>

```bash
# If GitHub CLI is installed.
gh api repos/actions/checkout/git/refs/tags/v6.0.3 --jq '.object.sha'
```

Inspect `.object.type` as well: a `tag` object must be dereferenced through the
Git tags API until its commit is resolved.

#### Method C: `curl` + `jq` [ᐞ](#table-of-contents)

<a id="method-c-curl-jq"></a>

```bash
# Get latest release metadata.
curl -s https://api.github.com/repos/actions/checkout/releases/latest \
  | jq -r '.tag_name'
```

Resolve that exact tag to its commit using Method A or B. The release's
`target_commitish` may be a branch name; it is not a verified release SHA.

### Step 3: Update workflow files [ᐞ](#table-of-contents)

<a id="step-3-update-workflow-files"></a>

Update every in-scope occurrence of that action across workflows and local
composite actions. Preserve intentionally different major-version lines.
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

### Step 4: Verify the update [ᐞ](#table-of-contents)

<a id="step-4-verify-the-update"></a>

Ensure all references were replaced:

```bash
# Search for old version.
grep -r "v6.0.2" .github/workflows/

# Verify new version.
grep -r "v6.0.3" .github/workflows/
```

### Step 5: Create a pull request [ᐞ](#table-of-contents)

<a id="step-5-create-a-pull-request"></a>

1. Create a branch: `git checkout -b chore/update-github-actions`
2. Commit updates with a clear message.
3. Push and open a PR.
4. Verify CI passes with new action versions.
5. Merge after approval.

---

## Checking for Updates [ᐞ](#table-of-contents)

<a id="checking-for-updates"></a>

### Method 1: Dependabot notifications [ᐞ](#table-of-contents)

<a id="method-1-dependabot-notifications"></a>

Dependabot can suggest action updates, but you still need to verify and update
the SHA manually.

Dependabot updates version tags in references. It does not select and verify the
new commit SHA for you.

Typical flow:

1. Review the Dependabot PR suggestion.
2. Fetch the SHA for that version.
3. Update the SHA in workflow files.

### Method 2: Automated check script (recommended) [ᐞ](#table-of-contents)

<a id="method-2-automated-check-script-recommended"></a>

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

* Discovers pinned actions dynamically from `.github/workflows/*.yml` and
  `.github/actions/*/action.yml`.
* Compares version comments against the latest version-like tag it discovers.
* Reports a SHA candidate for discovered updates; verify annotated tag targets
  separately before pinning them.
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

* `0`: no discovery warnings and no recorded updates; remote lookup failures
  are not guaranteed to produce a failing exit code.
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

* The script sorts version-like tags across major versions and may suggest a
  major upgrade. It does not enforce compatibility or reliably exclude
  prereleases, so treat every result as a candidate rather than approval to
  update.
* The script continues checks even when discovery warnings are present.
* Review release notes and compatibility, and obtain explicit agreement before
  applying a major upgrade suggestion.
* Discovery is line-based and limited to the paths above; inspect alternate
  YAML filenames and unusual reference forms separately when present.
* The script does not verify current tag/SHA parity, including moved tags.
  Review individual lookup results rather than trusting the final summary.

Note: the script reports updates but does not apply them.

### Method 3: GitHub security alerts [ᐞ](#table-of-contents)

<a id="method-3-github-security-alerts"></a>

GitHub can notify you about:

* Dependabot action update alerts.
* Security vulnerabilities in actions.
* Breaking changes in action releases.

Check: Repository Settings -> Code security and analysis -> Dependabot alerts.

---

## Testing Updates [ᐞ](#table-of-contents)

<a id="testing-updates"></a>

### 1. Test in a feature branch [ᐞ](#table-of-contents)

<a id="1-test-in-a-feature-branch"></a>

Always test action updates before merging:

```bash
git checkout -b test/action-update
# Make action updates.
git push origin test/action-update
# Create a PR and verify CI passes.
```

### 2. Verify action behavior [ᐞ](#table-of-contents)

<a id="2-verify-action-behavior"></a>

Review release notes for behavioral changes:

```text
Example: https://github.com/actions/checkout/releases/tag/v6.0.3
```

### 3. Monitor first run after merge [ᐞ](#table-of-contents)

<a id="3-monitor-first-run-after-merge"></a>

After merge:

* Watch the next CI run carefully.
* Check logs for new warnings or errors.
* Verify behavior still matches expectations.

---

## Understanding SHA Pins [ᐞ](#table-of-contents)

<a id="understanding-sha-pins"></a>

### What is a SHA pin? [ᐞ](#table-of-contents)

<a id="what-is-a-sha-pin"></a>

A SHA is a cryptographic fingerprint of a specific commit.

* Version tag (mutable): `actions/checkout@v6.0.2`
* Commit SHA (immutable):
  `actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd`

### Why use SHA pins? [ᐞ](#table-of-contents)

<a id="why-use-sha-pins"></a>

Tags can be moved. SHAs cannot be changed once published. Pinning SHAs keeps
workflow behavior stable and auditable.

### How GitHub Actions resolves references [ᐞ](#table-of-contents)

<a id="how-github-actions-resolves-references"></a>

GitHub supports both forms:

* Tag: `uses: actions/checkout@v6.0.2`
* Full SHA: `uses: actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd`
* Short SHA: `uses: actions/checkout@de0fac2e`

---

## Best Practices [ᐞ](#table-of-contents)

<a id="best-practices"></a>

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

## Troubleshooting [ᐞ](#table-of-contents)

<a id="troubleshooting"></a>

### "Action not found" error [ᐞ](#table-of-contents)

<a id="action-not-found-error"></a>

If this appears after updating:

```text
Error: Can't find 'node_modules/...' from action
```

The SHA may be incorrect. Verify it:

```bash
git ls-remote --tags https://github.com/actions/setup-node.git \
  refs/tags/v6.3.0 | awk '{print $1}'
```

### Workflows fail with a new action [ᐞ](#table-of-contents)

<a id="workflows-fail-with-a-new-action"></a>

Diagnose:

1. Check the action changelog for breaking changes.
2. Read workflow logs for precise errors.
3. Reproduce locally if possible.
4. Check the GitHub Actions status page.

Then:

* Revert to the previous pin while investigating.
* Open an issue in the action repository if needed.
* Contact maintainers when appropriate.

### SHA not found in repository [ᐞ](#table-of-contents)

<a id="sha-not-found-in-repository"></a>

Problem: fetched SHA does not exist.

Verify:

```bash
# Confirm repository and tag.
git ls-remote https://github.com/OWNER/REPO.git refs/tags/TAG_NAME

# Confirm tag exists.
git ls-remote https://github.com/OWNER/REPO.git | grep TAG_NAME
```

---

## Useful Resources [ᐞ](#table-of-contents)

<a id="useful-resources"></a>

* [GitHub Actions Security Hardening](https://docs.github.com/en/actions/security-guides)
* [Dependabot Documentation](https://docs.github.com/en/code-security/dependabot)
* [SLSA Framework](https://slsa.dev/)
* [GitHub Action Marketplace](https://github.com/marketplace?type=actions)

---

## FAQ [ᐞ](#table-of-contents)

<a id="faq"></a>

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

Review this workflow inventory against `.github/workflows/` whenever workflows
are added, removed, or renamed. See also [`doc/README.md`](README.md) and the
workflow files themselves.

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
