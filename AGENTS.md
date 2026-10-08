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
  recipes. Do not rely on remembered versions or assume an uninstalled
  component is available.
* This repository is a JSON REST API using Doctrine ORM, migrations, Symfony
  Serializer and Validator, Security, and Twig. API Platform, Messenger, and
  Lock are not current architectural assumptions; verify dependencies and
  configuration before using any component.
* Treat the existing implementation, tests, configuration, and CI as the
  source of truth when documentation conflicts with behavior.

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
  justified. Reuse existing dependencies first, review recipe-generated
  changes, and do not hand-edit bundle registration or base bundle
  configuration in place of a Flex recipe.
* Use constructor property promotion and `readonly` for suitable DTOs and
  value objects. Do not mark services `readonly` where lazy proxies may be
  used.

## Security and change scope [ᐞ](#table-of-contents)

<a id="security-and-change-scope"></a>

* Declare `declare(strict_types=1);` in every PHP file and provide explicit
  types. Keep changes compatible with PHPStan at max level, Psalm, PSR-12, and
  ECS; do not weaken types to satisfy tooling.
* Validate input with Symfony Validator constraints. Never remove or weaken
  authentication, authorization, or other security checks.
* Never commit secrets, JWT keys, or `.env.local`. See `doc/SECURITY.md` for
  the full security policy.
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
* Prefer existing `make` targets over running project tooling on the host. If
  container execution is unavailable and a host fallback is necessary, state
  that blocker in the handoff.
* Use `make lint-markdown` for Markdown checks. Do not install additional
  testing or linting tools when the repository already provides the relevant
  command.

## Testing and validation [ᐞ](#table-of-contents)

<a id="testing-and-validation"></a>

* Add or update tests for behavior changes. Exercise new behavior through the
  relevant caller boundary, such as an HTTP request for a controller or a
  service/resource call for business logic.
* Run the smallest relevant existing test or quality command while iterating.
  Before commit, run the CI-aligned suite from the repository root in the
  development container:

  ```bash
  make phpcs
  make ecs
  make phplint
  make php-parallel-lint
  make psalm
  make phpstan
  make phploc
  make phpinsights
  make check-security
  make lint-markdown
  make run-tests
  ```

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
* Do not create commits unless explicitly requested. At handoff, summarize
  changes, touched files, and validation run or skipped; include a proposed
  commit message for each logical change using `Type(scope): short description`.

## Reference guidance [ᐞ](#table-of-contents)

<a id="reference-guidance"></a>

* `.github/copilot-instructions.md` contains the concise repository rules.
* `CLAUDE.md` contains long-form architecture and workflow context.
* `doc/AI_RULES.md` explains AI policy maintenance and CI alignment.
* `.github/pull_request_template.md` contains the pull request review checklist.

If these documents drift, prefer the actual repository code, manifests,
configuration, and CI workflows, then update the guidance that is out of date.

---

[Back to previous](README.md)
