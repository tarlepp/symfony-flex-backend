---
name: add-custom-api-endpoint
description: Add or change a custom, non-CRUD API endpoint using this repository's controller, routing, DTO, authorization, service, API documentation, and HTTP-test conventions.
---

# What is this?

<a id="what-is-this"></a>

Use this skill for a custom API operation that does not fit the generic CRUD
controller lifecycle, such as a profile operation, relationship action, or
purpose-built lookup. Do not scaffold an entity, repository, resource, or CRUD
controller unless the requested behavior actually needs those layers. For a
complete CRUD feature, use [add-rest-resource](../add-rest-resource/SKILL.md).

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

1. Inspect the closest custom controller and its HTTP test under
   `src/Controller/` and `tests/E2E/Controller/`. Useful references include
   `Profile/IndexController`, `User/AttachUserGroupController`, and
   `User/DeleteUserController` for authenticated profile and state-changing
   operations. For small service-backed operational endpoints, also inspect
   `HealthzController` with `tests/E2E/Controller/HealthzControllerTest` and
   `VersionController` with `tests/E2E/Controller/VersionControllerTest`.
   These illustrate focused controllers delegating to services, documenting a
   response, and testing HTTP behavior and relevant side effects (such as
   avoiding request-log rows). Check their current behavior before reusing a
   pattern. Read route configuration and affected services, DTOs, callers, and
   API metadata as needed.
2. Define the endpoint contract before implementation: route and HTTP method,
   path/query/body inputs, response status and shape, validation and error
   behavior, authorization, and side effects. Preserve compatibility where
   applicable. Ask before making unspecified choices that change the public
   API, access policy, or persistent data.
3. Put a single custom endpoint action in a focused controller under
   `src/Controller/`, normally using `#[AsController]`, a route attribute, and
   `__invoke`. Use typed constructor injection and keep the controller at the
   HTTP boundary: coordinate the request and response, and delegate business
   rules or persistence orchestration to the appropriate existing resource or
   service. Do not add generic CRUD traits or duplicate behavior already owned
   by a resource.
4. Declare the intended HTTP methods and constrain route parameters where
   appropriate. Trace how route values become controller arguments, including
   entity resolution, and verify this against a nearby endpoint. Do not assume
   a route placeholder alone enforces the intended format or existence checks.
5. For request data, use a DTO with Symfony Validator constraints when the
   operation has structured or validated input. Keep accepted fields and
   response exposure explicit; never return entities or sensitive properties
   unintentionally. Inspect request-body handling before choosing how JSON is
   mapped. `RestDtoValueResolver` is for registered generic REST controller
   create/update/patch actions; do not assume it binds DTOs to custom
   `__invoke` controllers. Follow
   [add-api-dto-mapping](../add-api-dto-mapping/SKILL.md) when request mapping,
   DTO validation, or AutoMapper configuration changes.
6. Apply authorization explicitly at the endpoint or relevant service boundary
   using the established security attributes and voter patterns. Trace the
   actual caller identity, subject, and route argument binding; do not assume
   authentication alone permits the operation. Preserve existing firewall,
   role, voter, and access-control behavior. Follow
   [implement-api-authentication](../implement-api-authentication/SKILL.md)
   for authentication or broad access-policy changes, and
   [add-security-voter](../add-security-voter/SKILL.md) for subject-based
   permissions.
7. Document the public API with the project's OpenAPI attributes where
   applicable: operation or tag, inputs, request body, success response shape,
   and relevant validation or authorization errors. Keep the documented
   contract aligned with the actual route and response serialization context.
8. Add or update an E2E HTTP test under `tests/E2E/Controller/` that exercises
   the real route and relevant collaborators. Cover successful status and
   response shape, unauthenticated or forbidden callers as applicable, invalid
   inputs, and meaningful failure paths. For writes, assert persisted state or
   the resulting side effect, including idempotency or conflict behavior when
   part of the contract. Follow [write-tests](../write-tests/SKILL.md) for test
   structure, fixtures, and runner conventions.
9. If the endpoint changes persisted entity structure, follow
   [add-doctrine-entity](../add-doctrine-entity/SKILL.md) for repository,
   resource, migration, and schema work. If it adds repository filtering or
   search behavior, also follow
   [add-doctrine-query](../add-doctrine-query/SKILL.md). Avoid those layers
   for endpoint-only behavior that does not require them.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run the targeted E2E controller test inside the PHP development container,
  following shared guidance and the testing skill.
* Verify the route and allowed methods with the affected HTTP test or
  `bin/console debug:router`; verify authorization with both allowed and
  denied requests when access is restricted.
* For controller or service wiring changes, run
  `bin/console lint:container`. For configuration changes, use the relevant
  configuration lint command from shared guidance.
* Run relevant PHP quality checks from shared guidance. If OpenAPI metadata
  changes, verify the generated API documentation using the repository's
  existing workflow or tooling; a successful PHP lint does not verify the
  documented contract.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
