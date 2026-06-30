# What is this?

<a id="what-is-this"></a>

`AI_RULES.md` defines repository-level policy for AI-assisted changes and how
those expectations map to existing architecture, validation tooling, and CI.

## Table of Contents

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Why this document exists](#why-this-document-exists)
  * [Rule hierarchy](#rule-hierarchy)
  * [Scope](#scope)
  * [Core repository rules](#core-repository-rules)
    * [1. Follow the current Symfony architecture](#1-follow-the-current-symfony-architecture)
    * [2. Keep business logic in the right layer](#2-keep-business-logic-in-the-right-layer)
    * [3. Respect strict PHP and static analysis rules](#3-respect-strict-php-and-static-analysis-rules)
    * [4. Keep security intact](#4-keep-security-intact)
    * [5. Keep database changes consistent](#5-keep-database-changes-consistent)
    * [6. Keep changes small and relevant](#6-keep-changes-small-and-relevant)
    * [7. Use the running development container for project commands](#7-use-the-running-development-container-for-project-commands)
    * [8. Keep versioned documentation lightweight](#8-keep-versioned-documentation-lightweight)
    * [9. Require explicit commit authorization and clear handoff notes](#9-require-explicit-commit-authorization-and-clear-handoff-notes)
    * [10. Require clarification from the developer when context is missing](#10-require-clarification-from-the-developer-when-context-is-missing)
    * [11. Keep documentation aligned with code changes](#11-keep-documentation-aligned-with-code-changes)
  * [Enforcement model](#enforcement-model)
    * [Documentation-only rules](#documentation-only-rules)
    * [Automatically enforceable rules](#automatically-enforceable-rules)
  * [Current validation commands](#current-validation-commands)
  * [Current CI alignment](#current-ci-alignment)
  * [Recommended maintenance workflow](#recommended-maintenance-workflow)
  * [Contributor workflow for AI-assisted changes](#contributor-workflow-for-ai-assisted-changes)
  * [CI strategy for recurring AI mistakes](#ci-strategy-for-recurring-ai-mistakes)
    * [Examples of enforceable AI rules for this repository](#examples-of-enforceable-ai-rules-for-this-repository)
  * [Good rule-writing patterns](#good-rule-writing-patterns)
  * [Suggested next improvements](#suggested-next-improvements)
  * [Related files](#related-files)

## Why this document exists [ᐞ](#table-of-contents)

<a id="why-this-document-exists"></a>

This repository already has strong technical conventions, static analysis
tooling (PHPStan at max level, Psalm, ECS), a comprehensive test suite, and CI
workflows. This document defines how to turn those conventions into practical
rules for AI-assisted changes.

The goal is simple:

- improve the quality of AI-generated changes,
- reduce repository drift,
- keep changes aligned with Symfony and Doctrine project patterns, and
- back important rules with automated checks whenever possible.

## Rule hierarchy [ᐞ](#table-of-contents)

<a id="rule-hierarchy"></a>

Use the following order of precedence when maintaining AI guidance:

1. System or tool-level safety and platform rules
2. Repository instruction files such as `.github/copilot-instructions.md`
3. Project context and architecture guidance in `CLAUDE.md`
4. Existing code, lint rules, tests, and CI workflows as the final source of
   truth for implementation details

If a rule in a documentation file conflicts with the existing codebase or CI,
update the documentation or the implementation so that they match.

## Scope [ᐞ](#table-of-contents)

<a id="scope"></a>

These rules apply to AI-assisted changes in the repository, including:

- code generation,
- refactoring,
- documentation updates,
- tests, and
- dependency changes.

## Core repository rules [ᐞ](#table-of-contents)

<a id="core-repository-rules"></a>

### 1. Follow the current Symfony architecture

<a id="1-follow-the-current-symfony-architecture"></a>

- Use the resource-based REST architecture with a clear service layer.
- Place entities in `src/Entity/`, repositories in `src/Repository/`, resources
  in `src/Resource/`, and REST controllers in `src/Rest/`.
- Use DTOs in `src/DTO/` to control input/output; do not expose entities
  directly in API responses.
- Prefer existing shared building blocks before adding new abstractions.

### 2. Keep business logic in the right layer

<a id="2-keep-business-logic-in-the-right-layer"></a>

- Controllers should be thin and delegate to resource or service classes.
- Keep classes in `src/Controller/` as thin as possible.
- For custom controllers in `src/Controller/`, prefer the `__invoke` pattern
  (one controller class per endpoint).
- REST controllers in `src/Rest/` are an exception to the `__invoke` rule,
  because they follow existing trait-based controller patterns.
- Keep business logic in resource classes (`src/Resource/`) or services
  (`src/Service/`), not in controllers or repositories.
- Use repositories only for data access; use resources for orchestration.
- Use the AutoMapper (`src/AutoMapper/`) for entity-to-DTO and DTO-to-entity
  mapping.

### 3. Respect strict PHP and static analysis rules

<a id="3-respect-strict-php-and-static-analysis-rules"></a>

- Declare `declare(strict_types=1);` in every PHP file.
- Keep types explicit; avoid implicit `any` equivalents and weak type
  assertions.
- Avoid weakening types simply to make code compile or satisfy static analysis.
- Keep changes compatible with PHPStan at max level and Psalm.
- Follow PSR-12 and ECS coding standards.

### 4. Keep security intact

<a id="4-keep-security-intact"></a>

- Never remove or weaken authentication guards or security checks.
- Use Symfony Validator constraints for all input validation.
- Use DTOs to control what data is exposed in API responses.
- Never commit secrets, JWT keys, or environment overrides.

### 5. Keep database changes consistent

<a id="5-keep-database-changes-consistent"></a>

- Create Doctrine migrations when entity structure changes.
- Review generated migration files before running them.
- Validate the database schema with `bin/console doctrine:schema:validate`.

### 6. Keep changes small and relevant

<a id="6-keep-changes-small-and-relevant"></a>

- Prefer minimal, task-focused edits.
- Avoid unrelated refactors.
- Preserve public APIs unless the task requires a change.
- Reuse existing dependencies before proposing new ones.

### 7. Use the running development container for project commands

<a id="7-use-the-running-development-container-for-project-commands"></a>

- Treat the running `php` container or IDE Dev Container as the default
  environment for day-to-day development work.
- Use the `php` service container (`symfony-backend-php-fpm`) as the primary
  execution target for `composer`, `bin/console`, lint, test, and static
  analysis commands.
- If containers are not running, start them from project root with `make start`
  (foreground) or `make daemon` (background).
- Use `make bash` (or `make fish`) when you need an interactive shell in the
  running `php` container.
- Use containerized Node.js tooling (available via `nvm`) for documentation and
  markdown checks that rely on `npx`.
- When working from the host shell, prefer the existing `make` targets that
  call into the running container rather than executing project tooling on the
  host directly.
- Reserve host-level command execution for tasks that genuinely belong to the
  host environment, such as Docker lifecycle or Git operations.

### 8. Keep versioned documentation lightweight

<a id="8-keep-versioned-documentation-lightweight"></a>

- Avoid duplicating fast-changing dependency or tooling versions in long-form AI
  guidance when the repository already has a clear source of truth.
- Prefer referencing files such as `composer.json`, `Dockerfile`, and
  `phpstan.neon.dist` instead of maintaining the same version numbers in
  multiple documents.
- If an exact version matters for a change, read it from the source file rather
  than assuming that a documentation file is current.

### 9. Require explicit commit authorization and clear handoff notes

<a id="9-require-explicit-commit-authorization-and-clear-handoff-notes"></a>

- Do not create commits unless the developer explicitly requests a commit.
- Keep changes uncommitted by default while iterating with the developer.
- At task handoff, include a concise summary of what changed, which files were
  touched, and which validation commands were run or skipped.

### 10. Require clarification from the developer when context is missing

<a id="10-require-clarification-from-the-developer-when-context-is-missing"></a>

- If requirements are ambiguous, incomplete, or contradictory, ask the developer
  before implementation.
- Do not invent missing requirements, acceptance criteria, or runtime
  assumptions.
- Request explicit confirmation before non-trivial decisions that affect API
  contracts, database schema, security behavior, or architecture.
- If temporary assumptions are unavoidable, state them clearly and ask for
  confirmation in the handoff.

### 11. Keep documentation aligned with code changes

<a id="11-keep-documentation-aligned-with-code-changes"></a>

- Update relevant documentation when a change affects behavior, architecture,
  setup, workflow commands, or reviewer expectations.
- Prefer focused doc updates in existing files such as `README.md`, `doc/`,
  `.github/copilot-instructions.md`, and `CLAUDE.md` over creating parallel
  guidance.
- If no documentation update is needed, state that explicitly in the handoff
  summary.

## Enforcement model [ᐞ](#table-of-contents)

<a id="enforcement-model"></a>

Not all AI rules can be enforced automatically. Use the following model:

### Documentation-only rules

<a id="documentation-only-rules"></a>

These are guidance-heavy and should remain concise and stable:

- prefer minimal edits,
- follow existing project patterns,
- keep business logic out of controllers, and
- reuse existing shared building blocks first.

### Automatically enforceable rules

<a id="automatically-enforceable-rules"></a>

These should be validated through repository tooling and CI whenever possible:

- PHPStan static analysis (max level),
- Psalm type checking,
- ECS code style checks,
- PHPUnit test suite,
- database schema validation, and
- build success.

## Current validation commands [ᐞ](#table-of-contents)

<a id="current-validation-commands"></a>

From repository root inside the running development container, the main
validation commands are:

```bash
make ecs
make phpstan
make psalm
make run-tests
```

To auto-fix code style issues:

```bash
make ecs-fix
```

## Current CI alignment [ᐞ](#table-of-contents)

<a id="current-ci-alignment"></a>

At the time of writing, `.github/workflows/main.yml` already includes checks
for:

- PHP linting,
- ECS code style,
- PHPStan static analysis,
- Psalm type checking,
- PHPUnit test suite, and
- security vulnerability scanning.

That means the most effective starting point for AI rules in this repository is
not more process, but clearer instruction files that map directly to these
existing checks.

## Recommended maintenance workflow [ᐞ](#table-of-contents)

<a id="recommended-maintenance-workflow"></a>

When a repeated AI mistake appears, follow this sequence:

1. Decide whether the issue is a one-off or a recurring pattern.
2. If recurring, add or tighten a short repository rule.
3. If possible, back that rule with linting, tests, or CI.
4. Keep the rule short, concrete, and tied to a repository path or command.
5. Remove or simplify rules that no longer reflect the codebase.

As a practical default, update the AI rules when the same architectural or
review comment appears multiple times, or when a new project convention is added
that AI assistants should follow by default.

## Contributor workflow for AI-assisted changes [ᐞ](#table-of-contents)

<a id="contributor-workflow-for-ai-assisted-changes"></a>

When using AI assistance in this repository, keep the workflow lightweight:

1. Start with `.github/copilot-instructions.md` for short operational rules.
2. Use `CLAUDE.md` when you need broader project context or architecture notes.
3. Ask clarifying questions when requirements or constraints are unclear.
4. Make the smallest change that fits existing Symfony, Doctrine, and project
   patterns.
5. Run the smallest relevant validation commands for the files you changed
   inside the running development container.
6. Do not create commits unless the developer explicitly asks for a commit.
7. Provide a concise change summary at handoff, including file paths and
   validation status.
8. Update relevant documentation when the change alters behavior, architecture,
   or workflow expectations.
9. Use `.github/pull_request_template.md` as the review checklist when opening
   pull requests.
10. If a reviewer repeats the same correction pattern, update the AI guidance so
   future changes start from the improved rule.

## CI strategy for recurring AI mistakes [ᐞ](#table-of-contents)

<a id="ci-strategy-for-recurring-ai-mistakes"></a>

When the same AI-generated mistake appears repeatedly, prefer converting that
problem into an automated repository check instead of relying only on reviewer
memory.

Use this progression:

1. Document the rule in `.github/copilot-instructions.md` if it should affect
   day-to-day AI output.
2. Add or update tests, lint rules, or workflow checks if the mistake is
   machine-detectable.
3. Keep the check close to the existing project tool that already owns that
   concern.

For this repository, the preferred enforcement order is:

- ECS and PHP_CodeSniffer for coding style and formatting,
- PHPStan and Psalm for type safety and architectural violations,
- PHPUnit tests for behavior and service layer correctness,
- Doctrine schema validation for entity and migration consistency, and
- GitHub Actions workflow updates only when the existing commands are not
  enough.

Examples:

- If AI keeps placing business logic in controllers, add a PHPStan rule or
  review guidance that enforces delegation to resource classes.
- If AI keeps omitting strict types declarations, add a check via ECS or a
  custom PHPStan rule.
- If AI keeps exposing entities directly in API responses instead of using DTOs,
  add review guidance and a targeted test or architectural assertion.
- If AI keeps changing behavior without updating tests, add or expand targeted
  unit or integration tests in the affected feature.

Before adding a new CI rule, check that it is:

- specific to a recurring problem,
- understandable from the failure output,
- aligned with the current architecture, and
- unlikely to create noisy false positives.

Prefer extending existing jobs in `.github/workflows/main.yml` over creating a
new workflow unless the new check has a clearly different lifecycle or runtime
need.

### Examples of enforceable AI rules for this repository

<a id="examples-of-enforceable-ai-rules-for-this-repository"></a>

These are good candidates when a recurring AI mistake becomes common enough to
justify automation:

- Require `declare(strict_types=1);` in every PHP file where ECS or a custom
  check can enforce it.
- Require Doctrine migrations when entity files change.
- Require DTO usage for API input and output instead of raw entity exposure.
- Require PHPUnit test coverage for new resource or service class behavior.

Not every example needs immediate automation. Use them as a backlog of likely
enforcement candidates when the same class of AI-generated issue repeats.

## Good rule-writing patterns [ᐞ](#table-of-contents)

<a id="good-rule-writing-patterns"></a>

Prefer rules that are concrete and testable.

Better examples:

- New resource classes must extend or follow the existing `BaseResource` pattern
  under `src/Resource/`.
- New repositories must extend `BaseRepository` and live in `src/Repository/`.
- New user input must go through a DTO with Symfony Validator constraints.

Avoid vague rules such as:

- follow best practices,
- write clean code, or
- keep things consistent.

## Suggested next improvements [ᐞ](#table-of-contents)

<a id="suggested-next-improvements"></a>

After this first implementation, consider the following enhancements:

- evaluate which recurring AI issues from the examples section should become
  automated checks,
- tighten CI if a repeated class of regressions appears,
- add review checklist items for architectural exceptions, and
- periodically prune rules that duplicate lint or test enforcement.

## Related files [ᐞ](#table-of-contents)

<a id="related-files"></a>

- `README.md`
- `CLAUDE.md`
- `.github/copilot-instructions.md`
- `.github/pull_request_template.md`
- `.github/workflows/main.yml`

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
