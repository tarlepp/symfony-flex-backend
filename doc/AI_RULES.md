# What is this?

<a id="what-is-this"></a>

`AI_RULES.md` explains how to maintain this repository's AI guidance and how
to turn recurring, machine-detectable mistakes into existing tests or CI checks.
It does not restate day-to-day coding instructions.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Purpose and scope](#purpose-and-scope)
    * [Guidance roles and precedence](#guidance-roles-and-precedence)
    * [Maintaining task-specific skills](#maintaining-task-specific-skills)
    * [Enforcement model](#enforcement-model)
    * [Current CI alignment](#current-ci-alignment)
    * [Maintenance workflow](#maintenance-workflow)
    * [Turning recurring mistakes into checks](#turning-recurring-mistakes-into-checks)
    * [Writing effective rules](#writing-effective-rules)
    * [Further improvements](#further-improvements)
    * [Related files](#related-files)

## Purpose and scope [ᐞ](#table-of-contents)

<a id="purpose-and-scope"></a>

The actionable repository-wide requirements are in
[`AGENTS.md`](../AGENTS.md). This document has a narrower role: keep the AI
documentation system maintainable, and guide decisions about whether recurring
review feedback should become a documented rule, a task-specific skill, or an
automated check.

The canonical AI documentation inventory and reading order is in the
[README](../README.md#ai-assisted-development). Keep that inventory current
when AI guidance files are added, removed, or renamed.

## Guidance roles and precedence [ᐞ](#table-of-contents)

<a id="guidance-roles-and-precedence"></a>

Use the following precedence when instructions conflict:

1. System, platform, and tool instructions.
2. Repository-wide actionable requirements in [`AGENTS.md`](../AGENTS.md).
   [Copilot instructions](../.github/copilot-instructions.md) direct Copilot to
   that source rather than maintaining a second policy list.
3. Matching task-specific workflows in the
   [skills index](../.github/skills/README.md), which supplement repository-wide
   instructions.
4. Existing implementation, configuration, tests, lint rules, and CI as the
   source of truth for current behavior.

[`CLAUDE.md`](../CLAUDE.md) provides project architecture and workflow context.
The [pull request template](../.github/pull_request_template.md) is a review
checklist, not an instruction hierarchy layer.

The [project agent profiles](FEATURE_AGENTS.md) define feature-delivery
roles, approval gates, and specialist handoffs. They supplement repository
instructions and skills, not override them. Keep their routing, tool access,
and client limitations aligned when changing the workflow; only the feature
lead is user-facing.

When documentation conflicts with actual behavior, resolve the drift by
updating the inaccurate guidance or the implementation as appropriate; do not
preserve a rule that the project no longer follows.

## Maintaining task-specific skills [ᐞ](#table-of-contents)

<a id="maintaining-task-specific-skills"></a>

Use the [skills index](../.github/skills/README.md) for the skill format,
cross-linking, shared-guidance boundaries, and skill inventory requirements.
Keep common execution and handoff instructions in
[shared guidance](../.github/skills/shared-guidance.md); write domain workflows
and safeguards in their specific skills.

For changes to instructions, agent profiles, or skill contracts, use
[maintain-ai-guidance](../.github/skills/maintain-ai-guidance/SKILL.md) for
inspection targets, routing safeguards, and static versus live-client
validation boundaries.

Add a skill or expand shared policy only when a recurring need justifies it.
Before creating a new document, check whether the workflow belongs in an
existing skill or the shared guidance. New skills should cover a distinct
workflow rather than repeat repository-wide instructions or overlap an
existing skill.

## Enforcement model [ᐞ](#table-of-contents)

<a id="enforcement-model"></a>

Prefer concise instructions for expectations that require judgment, such as
scope control and following the closest existing pattern. For reliably
machine-detectable requirements, first use or extend the project tooling that
already owns that behavior.

Existing enforcement includes:

* ECS and PHP_CodeSniffer for coding style,
* PHPStan and Psalm for static analysis,
* PHPUnit for behavior,
* Doctrine schema validation for mapping consistency,
* configuration and Markdown linting, and
* CI jobs for dependency, secret, container-image, and build checks.

Do not add a parallel check when an existing tool or CI job can cover the
requirement clearly.

## Current CI alignment [ᐞ](#table-of-contents)

<a id="current-ci-alignment"></a>

Use `.github/workflows/main.yml` as the source of truth; this map is an
orientation and should be reviewed when that workflow changes. It currently
contains:

* change detection, which skips application tests and Docker builds for
  pull requests that change only Markdown files,
* configuration lint and documentation checks, including skill metadata,
  index coverage, and local Markdown links and anchors across the repository
  (project tooling uses Make targets inside the Compose `php` development
  service, including Python and Markdown checks),
* workflow-aware validation and embedded shell checks for all workflows,
  security audits of workflows and local actions with zizmor, plus separate
  schema validation for local action metadata,
* dependency and secret checks,
* Docker image pin verification and image builds/scanning,
* a static-analysis matrix,
* application tests,
* a prerequisite gate (`ci-summary`) that validates the lint, security and
  static-analysis job results,
* a final aggregate gate (`ci-result`) that validates the prerequisite gate,
  the static-analysis matrix, tests and Docker builds, and is the required
  merge check together with `dependency-review`, and
* optional coverage and Sonar reporting after successful pushes to `master`,
  outside the core CI gate; failures of those third-party steps do not fail
  the run.

The workflow also runs on `merge_group` events. Scheduled runs execute only
the periodic `security-check` and `docker-image-check-updates` jobs; every
other job is skipped. Skipped
results are accepted on a pull request only for tests and Docker builds of a
Markdown-only change. See [REQUIRED_CHECKS.md](REQUIRED_CHECKS.md) for the
merge-gate rules.

Separate workflows cover the rest of the pipeline: `dependency-review.yml`
(pull request dependency review), `vulnerability-scan.yml` (daily Trivy scan
of the development and production images, with SARIF upload), `scorecard.yml`
(OpenSSF Scorecard) and `codeql.yml` (CodeQL analysis of the workflow files).

For application tests, the workflow runs `make run-tests-php`. The local
`make run-tests` target may instead select phpdbg when it is installed. Use
`make run-tests-php` locally when reproducing the CI test runner.

The shared bootstrap starts the existing development image using
`make ci-start`; project Make targets execute in that container, and an
`always()` step calls `make ci-stop`. Docker orchestration, hosted scanners,
cache actions, and artifact uploads remain on the runner. Sonar receives
separate report copies with relative source paths, while container-based
uploaders and archived reports retain their original paths.

This CI coverage map is distinct from the broader local pre-commit suite in
[`AGENTS.md`](../AGENTS.md#testing-and-validation); that suite is not a
command-for-command description of CI. The workflow defines the exact
commands, conditions, matrices, artifacts, and failure behavior. Update this
summary when the workflow's structure changes, without duplicating the full
local checklist.

## Maintenance workflow [ᐞ](#table-of-contents)

<a id="maintenance-workflow"></a>

When AI-assisted work produces recurring review feedback:

1. Confirm that the correction reflects current project behavior and is not a
   one-off.
2. Decide where it belongs: repository-wide instructions, an existing or
   focused skill, shared guidance, a human review checklist, or an automated
   check. Prefer extending the closest existing source over creating
   overlapping guidance.
3. Add the rule at the narrowest appropriate location and link to an existing
   source instead of copying policy into several files.
4. If the rule is machine-detectable, prefer extending existing tooling or CI.
5. Remove obsolete or redundant guidance when conventions change.

## Turning recurring mistakes into checks [ᐞ](#table-of-contents)

<a id="turning-recurring-mistakes-into-checks"></a>

For a repeated, machine-detectable mistake, document the expected behavior in
the relevant instructions or skill, then assess whether a test, linter, or CI
check can prevent its recurrence. Keep enforcement near the code or tool that
owns the behavior, and extend an existing job in
[`.github/workflows/main.yml`](../.github/workflows/main.yml) unless a distinct
lifecycle justifies a separate workflow.

Before adding a check, confirm that it is:

* specific to a recurring problem,
* understandable from its failure output,
* aligned with current architecture, and
* unlikely to create noisy false positives.

Examples of potential checks include strict-type declarations, migrations when
entity mappings change, DTO use at API boundaries, and behavior tests for new
application logic. Treat these as candidates, not unconditional requirements:
inspect existing rules and tests before introducing enforcement.

## Writing effective rules [ᐞ](#table-of-contents)

<a id="writing-effective-rules"></a>

Prefer concrete, verifiable rules tied to a repository path or behavior:

* New repositories belong in `src/Repository/` and follow `BaseRepository`
  conventions.
* New API input is validated through the established DTO and Validator flow.
* Changes to externally observable behavior include tests at the appropriate
  caller boundary.

Avoid generic directions such as “follow best practices” or “write clean code.”
Do not repeat a check already enforced by tooling unless the rule explains a
distinct behavior the tool cannot verify.

## Further improvements [ᐞ](#table-of-contents)

<a id="further-improvements"></a>

Review recurring feedback periodically, tighten CI only for well-understood
failure patterns, and prune rules that duplicate other guidance or automated
checks.

## Related files [ᐞ](#table-of-contents)

<a id="related-files"></a>

* [README](../README.md#ai-assisted-development) - canonical AI guidance inventory
* [AGENTS.md](../AGENTS.md) - actionable repository-wide instructions
* [CLAUDE.md](../CLAUDE.md) - project architecture and workflow context
* [Copilot instructions](../.github/copilot-instructions.md) - Copilot entry point
* [Skills index](../.github/skills/README.md) - task-specific workflows
* [Shared skill guidance](../.github/skills/shared-guidance.md) - common skill workflow
* [Pull request template](../.github/pull_request_template.md) - review checklist
* [Main CI workflow](../.github/workflows/main.yml) - actual CI behavior

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
