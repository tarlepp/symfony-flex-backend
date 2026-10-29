# What is this?

<a id="what-is-this"></a>

This document describes which GitHub checks should block merging into
`master`, how stacked pull requests are merged, and how to configure and
verify the repository settings. The settings live in GitHub, not in the
repository, so this document is the source for what they should be.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Workflow overview](#workflow-overview)
    * [Current state](#current-state)
    * [Required checks](#required-checks)
    * [Protected branches](#protected-branches)
    * [Stacked pull requests](#stacked-pull-requests)
    * [Merge queue](#merge-queue)
    * [Changing required checks](#changing-required-checks)
    * [Troubleshooting](#troubleshooting)

## Workflow overview [ᐞ](#table-of-contents)

<a id="workflow-overview"></a>

Workflows live in `.github/workflows/`; `main.yml` is the core pipeline.

* `main.yml` (`CI`): pull requests, `master` pushes, `merge_group`, manual
  runs and a daily schedule. It is the only pull request pipeline that runs
  the application checks. Scheduled runs execute only `security-check` and
  `docker-image-check-updates`. Required through `Validate CI result`.
* `dependency-review.yml` (`Dependency Review`): pull requests. Reviews
  dependency changes for known vulnerabilities. Required as
  `dependency-review`.
* `vulnerability-scan.yml` (`Vulnerability Scan`): daily and manual. Builds
  and scans the development and production images with Trivy, enforcing
  `CRITICAL,HIGH` fixable findings, and uploads SARIF to code scanning. Not
  a pull request check, so never required.
* `scorecard.yml` (`Scorecard supply-chain security`): `master` pushes,
  weekly schedule and branch protection rule changes. Publishes OpenSSF
  Scorecard results. Never required.
* `codeql.yml` (`CodeQL`): pull requests, `master` pushes, weekly schedule
  and manual runs. Analyzes the workflow files only. Not required unless
  you decide that code scanning findings should block merging.

Jobs in `main.yml`, in dependency order:

* No dependencies: `Detect changed files`, the lint jobs
  (`Lint configuration`, `Lint documentation files`,
  `Validate GitHub workflows`), `PHP Security Checker`, `Gitleaks scan`,
  `Verify Docker base image pins (multi-platform)`,
  `Check Docker image versions` and the `Static analyzers (...)` matrix.
* `Validate CI prerequisites` (`ci-summary`): needs all of the above except
  change detection.
* `PHPUnit tests` and `Build application Docker image (...)`: need
  `ci-summary` and change detection; skipped for Markdown-only pull
  requests.
* `External reporting`: needs `PHPUnit tests`; runs only on `master` pushes.
* `Validate CI result` (`ci-result`): needs change detection, the
  prerequisite gate, the analyzer matrix, tests and Docker builds. This is
  the merge gate.

## Current state [ᐞ](#table-of-contents)

<a id="current-state"></a>

Inspected through the public GitHub API on 2026-10-29; bypass actors and
organization rulesets are not visible without admin access, so verify those
in the GitHub settings.

* No repository rulesets exist; `master` uses classic branch protection with
  enforcement for non-admins.
* The required checks match the recommended set below and are both from the
  GitHub Actions app (`app_id` 15368): `Validate CI result` and
  `dependency-review`. The earlier per-job contexts were removed.
* Everything else is covered through `Validate CI result` or intentionally
  not required: `Detect changed files`, `Validate CI prerequisites`, the
  lint and security jobs, `Validate GitHub workflows`, the
  `Static analyzers (...)` matrix, `Check Docker image versions`,
  `PHPUnit tests` and the Docker builds. `CodeQL`, `External reporting` and
  the scheduled workflows are not required.

Re-inspect and update this date whenever the settings change.

## Required checks [ᐞ](#table-of-contents)

<a id="required-checks"></a>

The check names below are the exact check-run contexts reported on a real pull
request run, not workflow display labels.

* `Validate CI result` (`main.yml`): final aggregate gate. It needs the
  prerequisite gate, the analyzer matrix, `PHPUnit tests` and both Docker
  builds, and fails unless all of them succeeded (skipped only on
  scheduled runs).
* `dependency-review` (`dependency-review.yml`): separate workflow, so the
  aggregate gate cannot cover it.

Require only these two. Jobs behind the aggregate gate, including
`Validate CI prerequisites`, `PHPUnit tests` and the Docker builds, do not
need their own entries, and adding a job or matrix target only means adding
it to the `needs` of `ci-result`.

Rules for this set:

* Documentation-only pull requests (every changed file is `*.md`) skip
  `PHPUnit tests` and the Docker builds. `Validate CI result` accepts those
  skips only when `Detect changed files` succeeded and reported no code
  changes on a `pull_request` event. Detection fails safe: any unclear case
  counts as a code change.

* Every new job in `main.yml` must be added to the `needs` of `ci-result`
  (directly or through a job that is), or it is not part of the merge gate.
* A job skipped because the gate failed counts as passing for GitHub. This is
  safe only while the gate itself is required and fails first.
* Matrix jobs need one required entry per expanded name. Adding a matrix
  target means adding its context here and in the branch protection.
* Select the GitHub Actions app as the source of each check, so a third-party
  app cannot satisfy it by reporting the same name.
* `External reporting` runs only on `master` pushes and must not be required.
* The scheduled runs of `main.yml` and `vulnerability-scan.yml` are not pull
  request checks and must not be required.
* The current per-job entries (lint, security, gitleaks, pin verification,
  PHPUnit, Docker builds) are redundant with `Validate CI result`. Remove
  them once the aggregate gate has run successfully on a pull request.

## Protected branches [ᐞ](#table-of-contents)

<a id="protected-branches"></a>

* Protect `master` first, with the required checks above, no force pushes,
  and no deletion. Prefer a repository ruleset over classic protection, and
  keep bypass actors to the minimum, such as the repository administrators.
  To migrate, create the ruleset for `master` in `Settings > Rules`, set
  it to `Evaluate` first, check the insights for unexpected blocks, switch
  it to `Active`, and only then delete the classic rule.
* Do not protect feature branches. Pull request CI runs for any base branch
  (`pull_request` has no branch filter), so stacked pull requests are tested
  without protecting their bases. Protecting them would also block the force
  pushes needed to rebase a stack.
* Do not use broad patterns such as `chore/*` or `chore(ci)/*` for stack
  bases. If one is ever needed, allow force pushes and deletion for it, and
  verify that ordinary pushes, rebases, and Dependabot or other automation
  still work on a throwaway branch before relying on it.
* Do not require branches to be up to date before merging. Pull request CI
  already runs against the merge result, and strict mode would force a rebase
  and a full CI run for each child after every parent merge. Revisit this if
  the merge queue is enabled.

## Stacked pull requests [ᐞ](#table-of-contents)

<a id="stacked-pull-requests"></a>

1. Merge the parent pull request first, into `master`.
2. Retarget each child pull request to `master`. GitHub does this
   automatically when the parent branch is deleted on merge.
3. Rebase the child onto the updated `master` and force push with
   `--force-with-lease`. With a squash or rebase merge of the parent, use
   `git rebase --onto master <old-parent-tip>` so the parent commits are not
   replayed.
4. Wait for the full CI run on the rebased child before merging it.

Never merge a child into its parent's branch as a substitute for retargeting.

## Merge queue [ᐞ](#table-of-contents)

<a id="merge-queue"></a>

The merge queue is not used. `main.yml` already has a `merge_group`
trigger, and change detection treats queue runs as code changes, so
`Validate CI result` reports for the queue's merge commit. Before enabling
it:

* Follow-up: `dependency-review.yml` still runs only on `pull_request`.
  GitHub only waits for required checks that report for the queue's merge
  commit, otherwise queued pull requests never merge. Verify that
  `dependency-review-action` supports the `merge_group` event and add the
  trigger, or remove `dependency-review` from the queue's required checks.
* Run a test pull request through the queue and verify that every required
  context reports.

## Changing required checks [ᐞ](#table-of-contents)

<a id="changing-required-checks"></a>

1. Change the workflow first and let it run on a pull request, so the new
   check context exists and is selectable in GitHub.
2. Read the exact contexts from the run, for example with
   `gh api repos/tarlepp/symfony-flex-backend/commits/<sha>/check-runs`
   and look at the `name` and `app.id` fields.
3. Add the new required check, merge the workflow change, and only then remove
   the old context. Renaming a job without this order leaves the pull request
   waiting forever for a check that no longer reports.
4. Compare the result with the live settings:
   `gh api repos/tarlepp/symfony-flex-backend/branches/master/protection`
   and `gh api repos/tarlepp/symfony-flex-backend/rulesets`.
5. Update this document in the same change.

## Troubleshooting [ᐞ](#table-of-contents)

<a id="troubleshooting"></a>

* `Validate CI result` fails after a skipped job: a skip is accepted only
  when `Detect changed files` succeeded and found a Markdown-only pull
  request. Otherwise a skipped `test` or `docker-build` means an upstream
  job failed or was cancelled; open the first failed job in the run.
* A required check stays pending forever: the context no longer reports,
  usually after renaming a job or workflow. Follow the order in
  [Changing required checks](#changing-required-checks) and remove the old
  context from branch protection.
* `CodeQL` fails with a default-setup conflict: `codeql.yml` and GitHub's
  CodeQL default setup cannot both be enabled. Disable one of them.

---

[Back to previous](README.md)
