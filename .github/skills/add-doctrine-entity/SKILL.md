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

Follow the shared skill guidance in `.github/skills/shared-guidance.md`.

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
7. Add tests for entity behavior, ORM mapping, persistence, and relationships
   as applicable, following `.github/skills/write-tests/SKILL.md`.
8. Add a corresponding repository under `src/Repository/`, extending or
    following `BaseRepository` and its entity-specific typing conventions.
    Reuse the existing repository methods and traits; add custom queries only
    when the entity requires them. Follow
    `.github/skills/add-doctrine-query/SKILL.md` for query-specific behavior
    and validation.
9. Add a corresponding resource under `src/Resource/`, extending
    `RestResource` with the entity and repository types. Keep orchestration and
    business behavior in the resource, and use the existing resource lifecycle
    patterns where relevant. An entity's repository and resource are required
    even when it has no CRUD controller.
10. If the request needs DTOs, request mapping, or a CRUD REST endpoint, follow
     `.github/skills/add-rest-resource/SKILL.md` for those parts. Add these
     layers only when required by the requested behavior.

## Validation

* Run `bin/console doctrine:schema:validate` and relevant migration checks
  against the test/development database when available.
