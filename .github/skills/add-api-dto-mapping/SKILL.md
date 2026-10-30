---
name: add-api-dto-mapping
description: Add or update API DTOs, validation constraints, AutoMapper request mappings, and request value resolution using this repository's create, update, and patch conventions.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding or changing an API DTO, its validation, request
mapping, or argument resolution without necessarily adding a complete CRUD
resource. For a full resource or persistence change, also follow
[add-rest-resource](../add-rest-resource/SKILL.md) or
[add-doctrine-entity](../add-doctrine-entity/SKILL.md) as appropriate.

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

1. Trace the affected endpoint through its controller DTO selection,
   `RestDtoValueResolver`, AutoMapper configuration,
   request mapper, DTO, and resource validation/persistence. Inspect nearby
   tests and `BodySubscriber` when request-body handling matters. Use
   `src/DTO/User/` and `src/AutoMapper/User/` as references, not as templates
   to copy without checking the target feature.
2. Establish which fields callers may supply, which operations accept them,
   and the required types, defaults, nullability, and response exposure.
   Distinguish omitted fields from explicit `null`, empty strings, empty
   arrays, `false`, and zero. Ask before changing an unspecified API contract,
   relationship replacement/addition policy, or authorization boundary.
3. Extend the existing DTO family under `src/DTO/`. REST DTOs follow
   `RestDto` and `RestDtoInterface`, with operation-specific create, update,
   and patch subclasses where needed. Keep properties, accessors, and
   collection types explicit. Setters for writable fields must record
   `setVisited()` consistently with the field's intended null semantics;
   do not mark omitted fields as visited or mark fields visited while loading
   existing entity state. Do not apply blanket readonly or constructor-only
   patterns to these mutable DTOs.
4. Add Symfony Validator constraints to the appropriate DTO properties or
   classes, preserving operation-specific requirements such as create-only
   password constraints. Reuse existing custom constraints in `src/Validator/`
   when appropriate. When creating or changing a project-specific constraint,
   also follow [add-validation-constraint](../add-validation-constraint/SKILL.md).
   Inspect `RestResource::getDtoForEntity()` and
   `RestResourceBaseMethods`: patch loads existing state before merging
   visited input and validating, whereas update starts from a fresh DTO.
   Do not weaken required-field validation to make partial requests pass.
5. Update the feature's `RequestMapper` under `src/AutoMapper/`, extending
   `RestRequestMapper`. Its `$properties` list is the writable-field allowlist;
   expose only intended inputs. The base mapper reads present fields from
   `$request->request`, calls matching setters, and uses
   `transform<Property>()` methods when defined. Keep conversions there rather
   than manually mapping request data in controllers or services. Preserve
   explicit failures for invalid values; do not silently substitute defaults,
   discard invalid references, or coerce incompatible input.
6. For relations and enums, follow nearby transformers and validation.
   Relation IDs can be resolved through the corresponding resource's
   `getReference()` and checked with `EntityReferenceExists`; obtaining a
   reference alone does not prove that the entity exists or that the caller
   may associate it. Preserve relevant authorization checks. Keep relationship
   application in existing DTO `$mappings` hooks, including replacement versus
   additive patch behavior such as `PatchUserGroups`. Keep unrelated business
   logic in resources or services.
7. Register every affected request DTO in the feature's
   `AutoMapperConfiguration::$requestMapperClasses`, extending
   `RestAutoMapperConfiguration` and injecting the feature request mapper.
   Check the controller's method-to-DTO selection. Reuse
   `RestDtoValueResolver`: it resolves `RestDtoInterface` arguments for
   supported actions in the registered controller collection, not arbitrary
   concrete DTO arguments or custom endpoints. Do not introduce a parallel
   request-binding mechanism or change shared resolvers just to support one
   field. If resolver changes are required, inspect service registration and
   preserve unrelated argument resolution and decorator behavior.
8. Keep DTO `load()`, visited-field updates, and custom mapping hooks consistent
   with the entity and resource flow. Inspect the actual response serializer
   context, groups, and OpenAPI metadata separately; a request DTO change does
   not automatically change response exposure. Never expose credentials or
   privileged fields incidentally. Preserve the endpoint's established output
   contract rather than adding a new response-mapping architecture as part of
   an input-only change. For shared serialization, population, or error output
   changes, use
   [update-api-response-contract](../update-api-response-contract/SKILL.md).
9. Inspect `tests/Integration/DTO/`, `tests/Integration/AutoMapper/`, and
   `tests/Integration/ValueResolver/RestDtoValueResolverTest.php` for existing
   helpers. Cover setters and visited fields, transformations, registered DTO
   mappings, validation, and entity updates as relevant. Exercise omitted
   versus explicit values, invalid enums or references, and create/update/patch
   differences. For API contract or wiring changes, also test through the real
   HTTP boundary, including JSON body handling, error responses, persistence,
   authorization, and sensitive-field exclusion where affected. Direct mapper
   calls alone do not verify the complete request flow.
10. Update relevant API metadata and existing documentation when the accepted
    input, output, validation, or patch semantics change. Do not generate a
    migration for a DTO-only change; use the entity skill when persisted
    mapping actually changes.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run the targeted DTO, mapping, resolver, and caller-boundary tests appropriate
  to the change inside the PHP development container, following shared guidance.
* For service registration or resolver wiring changes, run
  `bin/console lint:container` and verify resolution through the actual caller.
* Check response shape and persisted state when affected, not merely that the
  mapper returns an instance of the expected DTO class.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
