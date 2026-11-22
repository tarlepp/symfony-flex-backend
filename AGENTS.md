# What is this?

<a id="what-is-this"></a>

Repository-specific instructions for AI coding agents working on this Symfony
JSON REST API. Keep these instructions aligned with the established AI guidance
and the actual code, dependency manifests, and CI workflows.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Project context](#project-context)
    * [Architecture and Symfony conventions](#architecture-and-symfony-conventions)
    * [Security and change scope](#security-and-change-scope)
    * [Development workflow](#development-workflow)
    * [Testing and validation](#testing-and-validation)
    * [Clarification and documentation](#clarification-and-documentation)
    * [Reference guidance](#reference-guidance)

## Project context [ᐞ](#table-of-contents)

<a id="project-context"></a>

* Before changing framework-dependent code, check `composer.json` for the PHP
  and Symfony versions and available packages, and `symfony.lock` for Flex
  recipes. Do not rely on remembered versions or assume an uninstalled component
  is available.
* This repository is a JSON REST API using Doctrine ORM, migrations, Symfony
  Serializer and Validator, Security, and Twig. API Platform, Messenger, and
  Lock are not current architectural assumptions; verify dependencies and
  configuration before using any component.
* Treat the existing implementation, tests, configuration, and CI as the source
  of truth when documentation conflicts with behavior.

## Architecture and Symfony conventions [ᐞ](#table-of-contents)

<a id="architecture-and-symfony-conventions"></a>

* Follow the resource-based REST architecture: entities in `src/Entity/`,
  repositories in `src/Repository/`, resources in `src/Resource/`, DTOs in
  `src/DTO/`, and REST controllers in `src/Rest/`.
* Keep business logic in resources or services and controllers thin. Custom
  controllers in `src/Controller/` generally use one `__invoke` action per
  endpoint. The trait-based controllers in `src/Rest/` are an established
  exception.
* Use DTOs for API input and output rather than exposing entities. Reuse the
  AutoMapper in `src/AutoMapper/` and existing request value resolvers for
  mapping and request binding.
* Follow the existing repository patterns and extend `BaseRepository` for new
  repositories. Prefer extending existing resources and services over adding
  parallel abstractions.
* Use Symfony attributes and components where they fit the existing patterns.
  Preserve established configuration and routing conventions rather than
  imposing blanket rules or hand-writing framework infrastructure.
* Add Symfony packages with Composer/Flex when a capability is needed and
  justified. Reuse existing dependencies first, review recipe-generated changes,
  and do not hand-edit bundle registration or base bundle configuration in place
  of a Flex recipe.
* Use constructor property promotion and `readonly` for suitable DTOs and value
  objects. Do not mark services `readonly` where lazy proxies may be used.

## Security and change scope [ᐞ](#table-of-contents)

<a id="security-and-change-scope"></a>

* Declare `declare(strict_types=1);` in every PHP file and provide explicit
  types. Keep changes compatible with PHPStan at max level, Psalm, PSR-12, and
  ECS; do not weaken types to satisfy tooling.
* Validate input with Symfony Validator constraints. Never remove or weaken
  authentication, authorization, or other security checks.
* Never commit secrets, JWT keys, or `.env.local`. See `doc/SECURITY.md` for the
  full security policy.
* Make the smallest task-focused change, preserve public APIs, and avoid
  unrelated refactors or dependencies.
* For Doctrine entity changes, create and review a migration; do not update
  production schemas with `doctrine:schema:update` or hand-written SQL.

## Development workflow [ᐞ](#table-of-contents)

<a id="development-workflow"></a>

* Use the running PHP development container (`symfony-backend-php-fpm`) or IDE
  Dev Container for Composer, Symfony console, lint, test, and static-analysis
  commands. Start containers with `make start` or `make daemon`; use `make bash`
  or `make fish` for an interactive container shell.
* Follow [shared AI workflow guidance](.github/skills/shared-guidance.md) for task
  branch setup, handling pre-existing work, and commit-message proposals.
  Documentation-only changes use a `chore(docs)/...` branch and a
  `Chore(docs) - ...` pull request title; AI-guidance-only changes use
  `chore(ai)/...` and `Chore(ai) - ...`. State the base branch for every task;
  anything other than `master` is a stacked PR, so confirm the parent branch
  with the user.
* For every addition, modification, removal, or rename, review the skills that
  cover the affected behavior and paths. Update any skill made inaccurate by the
  complete task changes, including references and validation guidance, or report
  why no skill update is needed. Follow the [shared impact review](.github/skills/shared-guidance.md#updating-documentation);
  skill loading or selection metadata is not a delivery gate.
* Do not commit or push changes by default. Follow the
  [commit and push approval rules](.github/skills/shared-guidance.md#preparing-a-change):
  ask the user before either operation unless they explicitly authorized that
  operation for the current changes. Implementation or branch approval does not
  authorize committing or pushing; commit proposals are not actions.
* Run repository application tooling inside the Compose PHP development
  container in both local development and CI. Prefer existing `make` targets:
  host invocations forward project targets into the container. Docker
  orchestration and GitHub platform actions remain host-side. Do not treat
  `GITHUB_WORKFLOW` as proof that a command is inside a container, or fall back
  to host PHP, Composer, Python, or Node tooling when containers are
  unavailable; report the blocker instead.
* Use `make lint-markdown` for Markdown checks. Do not install additional
  testing or linting tools when the repository already provides the relevant
  command.
* Scope checks to the full task diff and affected behavior, not all commands
  listed in an agent or skill. Markdown additions, edits, renames, and deletions
  require `make lint-markdown`; skip it when neither Markdown nor its lint
  tooling/configuration changed. Follow the [shared selection rules](.github/skills/shared-guidance.md#running-and-validating-changes)
  for other linters, tests, builds, documentation links, final-content checks,
  and result reuse. Application configuration and dependency/runtime changes can
  require PHP checks and tests without any changed PHP source.

## Testing and validation [ᐞ](#table-of-contents)

<a id="testing-and-validation"></a>

* Add or update tests for behavior changes. Exercise new behavior through the
  relevant caller boundary, such as an HTTP request for a controller or a
  service/resource call for business logic.
* For targeted tests, run `phpunit <test-file-or-directory>` from the repository
  root inside the PHP development container; no explicit runner path is needed.
  See `.github/skills/write-tests/SKILL.md` for test selection and conventions.
* Run the smallest relevant existing test or quality command while iterating.
  Select validation by the behavior changed, not merely the file extension,
  using [the validation selection guide](doc/TESTING.md#selecting-validation). Documentation-only
  changes, including AI instructions and skill text, require Markdown lint and
  documentation checks, not PHPUnit or PHP static analysis. Apply the same scope
  before commit; a documentation example mentioning a test command does not
  require executing that command.
* Treat any confirmed failure in a required CI check selected by the task as a
  delivery blocker, even if it predates the branch. Fix it within scope or
  report the blocker and seek approval for out-of-scope remediation.
* For local pre-PR validation, run the CI-selected target from the repository
  root on the Docker host with development services running:

  ```bash
  make pre-pr-checks
  ```

  It applies the CI selector to the committed `BASE_SHA...HEAD_SHA` range
  (defaults: `HEAD^` and `HEAD`); uncommitted changes do not affect selection.
  Empty or uncertain comparisons conservatively select all local checks. Set
  `BASE_SHA` and `HEAD_SHA` to inspect another committed range.
* To always run every locally reproducible check regardless of changed files or
  commit range, run this from the same directory and host:

  ```bash
  make pre-pr-checks-all
  ```

  Either target can overwrite generated test/report files. The all-checks target
  always builds both image targets; selected mode builds them when the Docker
  category is selected. Image builds overwrite local tags
  `symfony-flex-backend:pre-pr-development` and
  `symfony-flex-backend:pre-pr-production`. They require Docker Buildx and
  registry access.
* Neither target reproduces GitHub-hosted checks, including Dependency Review,
  the Symfony Security Checker action, Gitleaks, Trivy scans/SARIF uploads, or
  optional external reporting. Scheduled and informational jobs are excluded.
  For normal task work, use the [validation selection guide](doc/TESTING.md#selecting-validation)
  instead. Merge gates and branch protection are described in
  `doc/REQUIRED_CHECKS.md`; every new `main.yml` job must be added to the
  `needs` of `ci-result`.

* If `make ecs` reports fixable style issues, run `make ecs-fix` and rerun
  `make ecs`.

## Clarification and documentation [ᐞ](#table-of-contents)

<a id="clarification-and-documentation"></a>

* Ask for clarification when requirements are unclear or when a non-trivial
  decision affects an API contract, database schema, security behavior, or
  architecture. Do not ask generic setup questions when the repository already
  establishes the relevant choices.
* Update relevant documentation when behavior, architecture, workflows, or
  commands change. For Markdown, follow `README.md` structure and list-format
  conventions.
* For every response containing Markdown, present the readable, rendered version
  first, followed by the same content in a fenced `markdown` code block for
  copying and pasting. Use a longer outer fence when the content includes code
  fences, and do not recursively duplicate the copyable version.
* Provide copy-pasteable instructions in every use case: setup, implementation,
  validation, troubleshooting, CI reproduction, and handoff. Put complete
  commands in fenced code blocks, identify whether they run on the Docker host
  or inside the container, include required setup and working directories, and
  avoid prompt prefixes or ambiguous alternatives in runnable blocks. Prefer
  host-safe Make targets; for direct project commands show how to enter the
  container first. Never include secret values in examples.
* At handoff, summarize changes, touched files, and validation run or skipped.
* End user-facing handoffs for completed changesets with proposed commit
  messages only when uncommitted task changes remain. Apply this to intermediate
  completed steps and the final handoff, whether or not a skill was used. See
  the [shared handoff guidance for checking and formatting proposals](.github/skills/shared-guidance.md#handoff).

## Reference guidance [ᐞ](#table-of-contents)

<a id="reference-guidance"></a>

Use the [README's canonical AI documentation inventory](README.md#ai-assisted-development)
to find project context, AI-guidance maintenance policy, task-specific skills,
and the pull request review checklist. The reading order is not precedence:
platform instructions apply first, and the implementation, tests, configuration,
and CI are the source of truth for current behavior.

---

[Back to previous](README.md)
