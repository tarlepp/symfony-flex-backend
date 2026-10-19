---
name: add-rest-resource
description: Add or extend a CRUD REST resource using this repository's Symfony, Doctrine, DTO, AutoMapper, and test patterns.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding a CRUD API resource or extending an existing
resource's input, output, or behavior.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Inspect the target endpoint's tests and a complete nearby feature.
   `ApiKey` is one reference implementation; confirm
   its patterns against current code before reusing them. Check existing routes,
   role requirements, DTO conventions, and migrations before editing.
2. Keep the change within the existing vertical-slice architecture:
   `Entity -> DTO / AutoMapper -> Repository -> Resource -> Controller`.
   Extend an existing class or pattern where appropriate; do not create a
   parallel abstraction.
3. For a persistence change, update the entity using Doctrine attributes and
   existing entity interfaces/traits. Add serialization groups and Symfony
   Validator constraints consistent with the field's API exposure and
   validation needs. Generate and review a Doctrine migration; do not use
   `doctrine:schema:update` or hand-write schema changes. Follow
   [add-doctrine-entity](../add-doctrine-entity/SKILL.md) for entity changes; every
   entity requires a corresponding repository and resource.
4. Define API input/output through DTOs in `src/DTO/`. Follow the existing
   `RestDto` patterns, validation constraints, and patch/visited-field behavior.
   Update `src/AutoMapper/` request mapping and configuration when request
   values need conversion (for example, resolving related entity IDs to entity
   references). Follow [add-api-dto-mapping](../add-api-dto-mapping/SKILL.md)
   for the detailed DTO, validation, mapping, and argument-resolution workflow.
   Do not expose entities directly in responses.
5. Add or update the repository in `src/Repository/`, following
   `BaseRepository` conventions and entity generics. Keep query/data-access
   behavior there; put orchestration and business rules in a resource or
   service. Follow [add-doctrine-query](../add-doctrine-query/SKILL.md) for custom
   queries, filtering, search, sorting, pagination, and database tests.
6. Add or update the resource in `src/Resource/`, extending `RestResource` and
   preserving its entity/repository types. Reuse lifecycle callbacks and
   repository helpers where they fit instead of duplicating generic CRUD
   behavior.
7. Add or update the controller and route using the closest existing endpoint
   pattern. Generic CRUD endpoints commonly extend `App\Rest\Controller`, use
   role-specific action traits, map create/update/patch methods to their DTO
   classes, and apply explicit route and authorization attributes. Keep access
   checks at least as restrictive as the intended API policy. Use a custom
   `__invoke` controller only when that matches the endpoint's existing style.
8. Exercise the changed actions through HTTP. Assert successful response status,
   shape, and field exposure, plus validation failures and permitted/denied
   callers where affected. For writes, verify persisted state and
   create/update/patch differences rather than only a successful status.
   Update fixtures only when the tests need the new data.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* For entity or migration changes, run the existing migration/schema checks,
  including `bin/console doctrine:schema:validate` when the test database is
  available.
* Verify registered routes, allowed HTTP methods, action-to-DTO selection, and
  response serializer groups through the affected endpoint tests. Inspect
  `ResponseHandler` and the resource's serializer context rather than assuming
  a request DTO's fields define the response.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
