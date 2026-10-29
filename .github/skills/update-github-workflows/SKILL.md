---
name: update-github-workflows
description: Add or change GitHub Actions workflows, CI jobs, triggers, matrices, and shared PHP bootstrap behavior while preserving this repository's prerequisite gates, test setup, and reporting safeguards.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when changing CI behavior in `.github/workflows/` or shared
steps in `.github/actions/`. For action-version and SHA-pin updates alone, use
[update-github-actions](../update-github-actions/SKILL.md). For Docker image
changes, also follow [update-docker-images](../update-docker-images/SKILL.md).

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Repository workflow map](#repository-workflow-map)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Repository workflow map [ᐞ](#table-of-contents)

<a id="repository-workflow-map"></a>

Read the current files before editing; this map is an orientation, not a
replacement for their actual event filters and conditions:

* `.github/workflows/main.yml`: configuration/documentation lint, dependency
  and secret checks, Docker pin verification, static-analysis matrix, tests,
  and development/production image builds with vulnerability scanning.
  It handles pushes to `master`, PRs targeting any branch, a schedule, and
  manual runs.
  Scheduled runs execute only the periodic `security-check` and
  `docker-image-check-updates` jobs; all other jobs are skipped.
* `.github/workflows/dependency-review.yml`: dependency review on PRs.
* `.github/workflows/scorecard.yml`: supply-chain analysis, artifact and SARIF
  uploads, with push, schedule, branch-protection, and manual triggers.
* `.github/workflows/vulnerability-scan.yml`: scheduled/manual development and
  production image builds and vulnerability scans; it does not run on ordinary PRs.
* `.github/actions/php-bootstrap/action.yml`: shared Compose development
  container setup, Composer download cache, optional installation, and version
  reporting.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Identify the intended behavior and affected events, jobs, and callers.
   Read the relevant Make targets, scripts, bootstrap inputs, and configuration
   before adding shell commands. Reuse existing checks rather than creating a
   second implementation. Ask before changing event coverage, required-check
   identities, permissions, or the policy for blocking tests/builds.
2. Preserve event and execution semantics. Check PR base-branch versus push
   branch filters, schedule timing in UTC, manual-run inputs, job/step `if`
   expressions, and concurrency groups/cancellation. Do not assume the default
   branch is named `main`. Consider same-repository PRs, fork PRs, pushes, and
   non-PR events explicitly; not every event supplies a pull-request payload.
   Path filters or job renames can affect required checks; confirm repository
   rules when relevant rather than assuming they are defined in YAML.
3. Maintain the job dependency graph. In `main.yml`, `ci-summary` runs with
   `always()` and explicitly requires successful prerequisite results (skipped only
   for non-periodic jobs on `schedule`, never on PRs or pushes); tests
   and Docker builds depend on that gate. When adding, removing, or renaming
   a prerequisite, update its `needs` entry and result checks/messages
   together. Check failure, cancellation, and skipped-job paths, not only
   success. Do not use `continue-on-error` or permissive result checks to make
   a required failure appear green.
4. Preserve shared setup contracts. Inspect all callers before changing
   `php-bootstrap` defaults or input names. Its boolean-like inputs are
   strings compared with `'true'`; preserve compatible caller values.
   The test job deliberately defers dependency installation and version
   reporting until after copying `.env.gh-actions` and generating JWT keys.
   The PHP runtime, extensions, and coverage driver come from the development
   Dockerfile, not runner PHP setup. Keep service health checks and the
   dynamically assigned MariaDB host port reachable via `host.docker.internal`
   and forwarded into the PHP container.
   Do not copy CI environment files over a developer's local configuration
   merely to validate a workflow edit.
5. Keep runner and command assumptions explicit. Check PHP requirements in
   `composer.json` and actual tools/extensions in the bootstrap. Inspect
   Makefile environment detection: local and hosted project commands execute
   inside the Compose PHP development service through Make targets.
   `make ci-start` starts that service without the application entrypoint or
   dependencies, and `make ci-stop` must run with `always()` after consumers.
   Docker/Compose checks and GitHub platform actions run on the host.
   Preserve YAML anchors and aliases
   in `main.yml`; changing an anchored checkout or hardening step affects
   every alias. Retain suitable timeouts and intentional matrix `fail-fast`
   behavior; inspect all matrix variants affected by a shared change.
6. Preserve trust boundaries and least-privilege intent. Keep external actions
   SHA-pinned and runner hardening in place. Review permissions at workflow
   and job scope, checkout credentials, cache keys/restore scope, and shell
   handling of untrusted event values. Do not switch to `pull_request_target`,
   execute untrusted PR code with privileged credentials, or expose secrets
   to make a job work. Pass untrusted values through quoted environment
   variables instead of interpolating them into shell source.
7. Preserve reporting and artifact contracts. External coverage/Sonar uploads
   run only after successful pushes to `master`, outside the core CI gate, and
   require their optional token. Keep core tests independent of those uploads.
   Verify producer paths match upload paths for coverage, analyzer reports,
   metrics, and SARIF; keep names unique across matrix jobs where required. Do
   not upload JWT keys, environment secrets, or unrelated workspace contents.
   Keep vulnerability thresholds and scan failure behavior unless a policy
   change is explicitly requested.
8. Update directly related contributor or AI guidance when CI commands or
   required checks change, including
   [AI rules](../../../doc/AI_RULES.md) where applicable. Delegate pin
   verification to the action-update skill rather than duplicating that
   procedure. Do not push, dispatch workflows, or open a PR unless requested.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Inspect the diff and every affected caller, anchor alias, matrix variant,
  `needs` reference, condition, and artifact consumer. Trace expected outcomes
  for success, failure, skipped prerequisites, and events without secrets.
* Run the smallest relevant existing commands locally. For analyzer behavior,
  use `make phpstan` or `make psalm` instead of GitHub-formatted targets. For
  test behavior, use the [testing skill](../write-tests/SKILL.md); a targeted
  test does not verify coverage artifact generation. Exercise the report
  producer when changing its paths or upload contract.
* For Compose validation changes, run these commands from the host:

  ```bash
  docker compose config --quiet
  docker compose -f compose.yaml -f compose.prod.yaml config --quiet
  ```

* `make lint-yaml` validates Symfony `config/`, not GitHub workflows. The
  action-update checker inventories pins, not job graphs or expression
  semantics. Run `make lint-workflows` for actionlint (including YAML anchors
  and aliases), ShellCheck on supported workflow shell steps, and separate
  action-validator schema checks of local action metadata. These checks do
  not execute jobs or validate shell bodies inside composite actions; do not
  report static validation as a successful workflow execution.
* When an authorized push or PR produces a run, inspect affected job logs,
  matrix results, prerequisite gating, and expected artifacts. Separate
  runtime evidence from static review and local command results. Scheduled
  or manual-only workflows and unavailable fork/secret conditions remain
  unverified unless actually exercised; state those gaps explicitly.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
