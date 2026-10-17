---
name: add-rest-resource
description: Add or extend a CRUD REST resource using this repository's Symfony, Doctrine, DTO, AutoMapper, and test patterns.
---

# Add a REST resource

Use this skill when adding a CRUD API resource or extending an existing
resource's input, output, or behavior.

Before applying this skill, read `.github/copilot-instructions.md` and
`doc/AI_RULES.md`. Follow those general repository rules alongside this
task-specific workflow; they take precedence if guidance conflicts.

## Workflow

1. Inspect `composer.json`, `symfony.lock`, the target endpoint's tests, and a
   complete nearby feature. `ApiKey` is one reference implementation; confirm
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
   `.github/skills/add-doctrine-entity/SKILL.md` for entity changes; every
   entity requires a corresponding repository and resource.
4. Define API input/output through DTOs in `src/DTO/`. Follow the existing
   `RestDto` patterns, validation constraints, and patch/visited-field behavior.
   Update `src/AutoMapper/` request mapping and configuration when request
   values need conversion (for example, resolving related entity IDs to entity
   references). Do not expose entities directly in responses.
5. Add or update the repository in `src/Repository/`, following
   `BaseRepository` conventions and entity generics. Keep query/data-access
   behavior there; put orchestration and business rules in a resource or
   service.
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
8. Add tests at the affected layers: DTO validation/mapping, resource and
   repository integration where relevant, and HTTP behavior for controller
   changes. Include authorization and validation outcomes when behavior depends
   on them. Update fixtures only when the tests need the new data.
9. Update API documentation attributes or relevant project docs if the change
   affects the documented contract or usage.

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

* Run targeted `phpunit` tests for the changed layers; run `make run-tests`
  when the change warrants broader coverage.
* For entity or migration changes, run the existing migration/schema checks,
  including `bin/console doctrine:schema:validate` when the test database is
  available.
* Run relevant quality checks, including `make ecs`, `make phpstan`,
  `make psalm`, and `make lint-markdown` if this skill or other Markdown files
  changed.

Before handoff, state what changed, which validation ran or was blocked, and
whether any migration needs to be applied. Do not commit unless explicitly
requested.
