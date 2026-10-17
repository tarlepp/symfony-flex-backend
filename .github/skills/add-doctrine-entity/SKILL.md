---
name: add-doctrine-entity
description: Add or change a Doctrine ORM entity and its migration, tests, and related persistence mapping following this repository's conventions.
---

# Add a Doctrine entity

Use this skill when adding a persisted domain entity or changing an existing
entity's Doctrine mapping. In this repository, every entity must have a
corresponding repository and resource. A CRUD REST endpoint, DTOs, and
AutoMapper configuration are separate concerns and should be added when the
requested behavior needs them.

## Workflow

1. Check `composer.json` for the current PHP, Doctrine ORM, and migrations
   versions, and `symfony.lock` for installed recipes. Inspect a similar entity
   and its tests under `src/Entity/` and `tests/` before choosing a pattern.
   Use `AGENTS.md` and the existing implementation as the architectural source
   of truth.
2. Confirm what the entity needs to support: REST input/output, relations,
   audit fields, and soft/hard deletion. Reuse an existing entity trait or
   shared pattern only when its behavior fits; do not add unrelated fields.
3. Place the class in `src/Entity/` and use Doctrine ORM PHP attributes.
   Persisted application entities normally implement
   `App\Entity\Interfaces\EntityInterface`; follow the existing UUID,
   timestamp, and blameable conventions where applicable. Check how the
   interface and traits behave before using them, and account for read-only or
   derived entities that have different requirements.
4. Define column types, nullability, lengths, indexes, unique constraints, and
   relationship ownership explicitly and consistently with the schema. Use
   typed properties and methods. For relationships, update both sides only
   where the existing bidirectional association pattern requires it. Avoid
   accidental lazy-loading or serialization cycles.
5. If entity properties are exposed through the API, add the appropriate
   Serializer groups and OpenAPI attributes following nearby entities. Keep
   API exposure intentional; do not expose sensitive fields. Put request
   validation constraints on the appropriate DTO as well as entity-level
   constraints only where existing patterns call for them.
6. Generate a migration inside the PHP development container with
   `bin/console doctrine:migrations:diff`. Review the generated SQL and
   migration class for data safety, nullability, indexes, foreign keys,
   defaults, and reversible behavior. Edit the generated migration when
   needed. Never use `doctrine:schema:update` or hand-write ad hoc SQL to
   change a live schema. Do not run migrations against production.
7. Add focused tests under `tests/Unit/Entity/` for entity behavior that can be
   verified without persistence, and under `tests/Integration/Entity/` for ORM
   mapping, persistence, or relationship behavior. Follow the existing test
   base classes and fixtures; add fixtures only when needed.
8. Add a corresponding repository under `src/Repository/`, extending or
    following `BaseRepository` and its entity-specific typing conventions.
    Reuse the existing repository methods and traits; add custom queries only
    when the entity requires them.
9. Add a corresponding resource under `src/Resource/`, extending
    `RestResource` with the entity and repository types. Keep orchestration and
    business behavior in the resource, and use the existing resource lifecycle
    patterns where relevant. An entity's repository and resource are required
    even when it has no CRUD controller.
    Add corresponding repository and resource integration tests using the
    existing test base classes.
10. If the request needs DTOs, request mapping, or a CRUD REST endpoint, follow
     `.github/skills/add-rest-resource/SKILL.md` for those parts. Add these
     layers only when required by the requested behavior.

## Validation

From the host machine, run `make bash` (or `make fish`) in the repository root
to open a shell in the PHP Docker container. If the containers are stopped,
start them first with `make start`, then open the container shell from a
second host terminal. An IDE Dev Container terminal is already inside the
container.

Run project commands from the repository root inside the running PHP
development container, not on the host machine. Invoke `phpunit` directly
for targeted tests, without a `./vendor/bin/` prefix or host-side Docker
wrapper. Follow `.github/skills/write-api-tests/SKILL.md` for test guidance.

* Run focused entity, repository, and resource tests with `phpunit`, then the
  broader `make run-tests` suite when warranted.
* Run `bin/console doctrine:schema:validate` and relevant migration checks
  against the test/development database when available.
* Run `make ecs`, `make phplint`, `make phpstan`, and `make psalm` for PHP
  changes. Run `make lint-markdown` when this skill or other Markdown files
  changed.

Report the migration file and whether it has only been generated/reviewed or
also applied to a non-production database. Mention any validation that could
not run and why. Do not commit unless explicitly requested.
