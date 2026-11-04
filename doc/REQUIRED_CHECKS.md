# What is this?

<a id="what-is-this"></a>

This document describes which GitHub checks should block merging into
`master`, how stacked pull requests are merged, and how to configure and
verify the repository settings. The settings live in GitHub, not in the
repository, so this document is the source for what they should be.

For workflow triggers, execution graphs, job details, reports and local
reproduction, see the [GitHub workflow guide](GITHUB_WORKFLOWS.md).
This document stays focused on merge protection and repository settings.

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

Core CI in [`main.yml`](../.github/workflows/main.yml) aggregates its checks
through `Validate CI result`. The separate
[`dependency-review.yml`](../.github/workflows/dependency-review.yml) reports
`dependency-review`. Require both; the core aggregate cannot cover a separate
workflow.

The [event tables](GITHUB_WORKFLOWS.md#when-workflows-run) and
[job dependency graph](GITHUB_WORKFLOWS.md#job-dependency-graph) explain which
jobs run and how they reach the gate. Eligible external reporting is enforced
through `Validate CI result`, not a separate required context. Scheduled
maintenance, Scorecard and CodeQL are not additional required checks under the
current policy.

Confirmed title/body-only PR edits use the distinct skipped context
`Metadata-only PR edit (no validation)`. Never require that context or rename
it to `Validate CI result`: a skipped metadata check must not mask an earlier
failed validation of the same commit. Existing required validation remains
authoritative; a metadata edit does not repair failed CI.

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
  `PHPUnit tests`, the Docker builds and `External reporting`. `CodeQL` and
  the scheduled workflows are not required.

Re-inspect and update this date whenever the settings change.

## Required checks [ᐞ](#table-of-contents)

<a id="required-checks"></a>

The check names below are the exact check-run contexts reported on a real pull
request run, not workflow display labels.

* `Validate CI result` (`main.yml`): final aggregate gate. It needs the
  prerequisite gate, the lightweight analyzers, the analyzer matrix,
  `PHPUnit tests`, eligible `External reporting` and both Docker builds, and
  fails unless all eligible jobs succeeded. Application jobs skip only on
  schedules or confirmed Markdown-only PRs; reporting additionally skips merge
  groups and manual runs.
* `dependency-review` (`dependency-review.yml`): separate workflow, so the
  aggregate gate cannot cover it.

Require only these two. Jobs behind the aggregate gate, including
`Validate CI prerequisites`, `PHPUnit tests` and the Docker builds, do not
need their own entries. Keep new jobs covered by the aggregate's dependency
graph and result checks; a new variant of an already-covered matrix remains
covered without a separate branch-protection entry.

Rules for this set:

* Documentation-only pull requests (every changed file is `*.md`) skip
  application configuration checks, PHP dependency security, Docker pin/update
  checks, both PHP analyzer jobs, `PHPUnit tests` and Docker builds.
  Documentation checks, workflow validation, Gitleaks, Dependency Review and
  CodeQL remain enabled. Both gates accept the listed skips only when
  `Detect changed files` succeeded and reported `code=false` on a
  `pull_request` event. Detection fails safe: any unclear case counts as code.
  Renames include both paths, so code renamed to Markdown still runs full CI.

* Every new job in `main.yml` must be added to the `needs` of `ci-result`
  (directly or through a job that is), or it is not part of the merge gate.
* A job skipped because the gate failed counts as passing for GitHub. This is
  safe only while the gate itself is required and fails first.
* If requiring a matrix job directly instead of through the aggregate, each
  expanded name needs its own entry. The current policy uses the aggregate,
  so adding a matrix target does not require a new required context.
* Select the GitHub Actions app as the source of each check, so a third-party
  app cannot satisfy it by reporting the same name.
* `External reporting` is eligible on PRs and successful `master` pushes and
  is enforced through `Validate CI result`, not a separate required context.
  Its job must succeed even when optional upload tokens are absent; individual
  uploads still skip without their tokens.
* The scheduled runs of `main.yml` and `vulnerability-scan.yml` are not pull
  request checks and must not be required.
* Per-job entries (lint, security, Gitleaks, pin verification, PHPUnit,
  Docker builds) would be redundant with `Validate CI result`; do not
  reintroduce them when updating branch protection.

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

The merge queue is not enabled by these workflow changes. Both `main.yml` and
`dependency-review.yml` handle `merge_group: checks_requested`, retaining
`Validate CI result` and `dependency-review` as the required contexts.
Change detection treats queue runs as code changes, so all core checks run.
The pinned Dependency Review v5 action natively compares the payload's
`merge_group.base_sha` and `merge_group.head_sha`; it does not require a PR
payload or custom ref overrides. Its vulnerability policy and read-only
permissions remain unchanged.

Native merge-group support was verified in the
[pinned action's ref selection](https://github.com/actions/dependency-review-action/blob/a1d282b36b6f3519aa1f3fc636f609c47dddb294/src/git-refs.ts).
Offline workflow regressions protect both triggers and the required-check
contracts; they do not verify GitHub's dependency-graph comparison API.

Before relying on a merge queue:

* Merge the workflow changes into the target branch first.
* Obtain approval to enable the queue in GitHub settings; retain both required
  contexts from the GitHub Actions app.
* Run a test pull request through the queue and verify both required contexts
  report for the queue's merge commit, not just the PR head.
* Inspect Dependency Review logs for a successful base/head comparison and
  core CI for successful tests and both image builds. Test failure handling
  before relying on automatic merges; missing or failed checks must block.

Queue configuration and hosted merge-group execution remain unverified until
that live test is completed.

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
  request, and only for the jobs listed above (or scheduled periodic-only
  coverage). Documentation, workflow and Gitleaks skips are never accepted
  on PRs. Otherwise open the first failed or cancelled prerequisite.
* A required check stays pending forever: the context no longer reports,
  usually after renaming a job or workflow. Follow the order in
  [Changing required checks](#changing-required-checks) and remove the old
  context from branch protection.
* `CodeQL` fails with a default-setup conflict: `codeql.yml` and GitHub's
  CodeQL default setup cannot both be enabled. Disable one of them.

---

[Back to previous](README.md)
