# Copilot instructions for this repository

## Purpose

Use these instructions as the repository-level default when proposing or making
changes in this project. Keep changes aligned with the existing Symfony, Doctrine
ORM, and project workflow conventions.

## Architecture rules

- Follow the existing resource-based REST architecture with a clear service layer.
- Keep entities in `src/Entity/`, repositories in `src/Repository/`, resources in
  `src/Resource/`, and REST controllers in `src/Rest/`.
- Keep classes in `src/Controller/` as thin as possible and delegate business
  logic to resources/services.
- For custom controllers in `src/Controller/`, prefer the `__invoke` pattern
  (one controller class per endpoint).
- REST controllers in `src/Rest/` are an exception to the `__invoke` rule,
  because they use existing trait-based controller patterns.
- Use DTOs in `src/DTO/` to control input/output data; do not expose entities
  directly in controller responses.
- Keep business logic in resource classes or services, not in controllers.
- Use the AutoMapper (`src/AutoMapper/`) for entity-to-DTO and DTO-to-entity
  mapping instead of doing manual mapping in controllers or services.
- Respect existing repository patterns; extend `BaseRepository` when creating new
  repositories.
- Prefer extending an existing resource or service before creating a new parallel
  pattern.

## PHP and Symfony rules

- Declare `declare(strict_types=1);` at the top of every PHP file.
- Use PHP 8.4+ features including constructor promotion, readonly properties, and
  named arguments where appropriate.
- Respect strict PHPStan (max level) and Psalm rules; avoid suppression comments
  unless there is no other option.
- Use type hints for all parameters and return types.
- Follow PSR-12 coding style and ECS (Easy Coding Standard) conventions.
- Use Symfony Validator constraints for all input validation.
- Never remove security checks or authentication guards.

## Security rules

- Never commit secrets, JWT keys, or `.env.local` files.
- Always validate input with Symfony validation constraints.
- Respect role-based access control; do not weaken security guards.
- Use DTOs to control what data is exposed in API responses.

## Change scope rules

- Prefer the smallest change that fully solves the task.
- Do not refactor unrelated code unless the task requires it.
- Do not introduce new dependencies unless they are necessary and justified.
- Preserve public APIs and existing architecture unless the task explicitly
  requires a change.
- Update relevant documentation when code changes affect behavior, architecture,
  workflows, commands, or contributor expectations.

## Collaboration and commit rules

- Do not create commits unless the developer explicitly asks for a commit.
- Keep work as uncommitted changes until commit instructions are provided.
- After each completed task, provide a concise summary of what changed,
  including affected files and validation commands that were run (or skipped).

## Clarification and assumptions rules

- If requirements are ambiguous or incomplete, ask the developer before
  implementing.
- Do not assume hidden requirements, expected behavior, or acceptance criteria;
  request confirmation when uncertain.
- Ask for explicit decisions before making non-trivial choices that affect API
  behavior, database schema, security, or architecture.
- If you must proceed with a temporary assumption, state it clearly and ask for
  confirmation in the response.

## Command execution rules

- Treat the running `php` development container or IDE Dev Container as the
  default environment for development commands.
- Use the `php` service container (`symfony-backend-php-fpm`) as the primary
  execution target for project commands.
- If containers are not running, start them from project root with `make start`
  (foreground) or `make daemon` (background).
- Use `make bash` (or `make fish`) to open an interactive shell in the `php`
  container before running commands manually.
- Run `composer`, `bin/console`, lint, test, and static analysis commands inside
  the running container, not on the host machine.
- Node.js tooling is available in the containerized environment (via `nvm`), so
  use in-container `npx` commands for documentation checks when needed.
- If starting from the host, prefer the existing `make` targets that delegate
  into the running container.
- Only run project commands directly on the host when the task explicitly
  requires host-level Docker or Git operations.

## Validation rules

After changing code, prefer running the smallest relevant validation set from
project root inside the running development container:

- `make ecs`
- `make phpstan`
- `make psalm`
- `make run-tests`

## Reference documentation

For deeper project context, architecture notes, and workflow details, use
`CLAUDE.md` as the long-form reference document.

For guidance on maintaining AI policy files and turning recurring issues into
validation or CI checks, use `doc/AI_RULES.md`.
