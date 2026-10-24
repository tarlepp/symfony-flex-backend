---
name: add-validation-constraint
description: Add or change a Symfony Validator constraint and validator in this repository, including input semantics, service dependencies, error codes, and tests.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding or changing a project-specific Symfony validation
rule under `src/Validator/Constraints/`. For using constraints on API DTOs,
also follow [add-api-dto-mapping](../add-api-dto-mapping/SKILL.md); a custom
constraint does not require changing a DTO if its validation scope is elsewhere.

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

1. Inspect the relevant DTO or domain object, existing constraint and validator
   pairs under `src/Validator/Constraints/`, and nearby tests under
   `tests/Integration/Validator/Constraints/`. Examples include
   `Locale`/`LocaleValidator`, `Timezone`/`TimezoneValidator`, and
   `UniqueUsername`/`UniqueUsernameValidator`. Check their current behavior and
   the installed Symfony Validator APIs before applying a pattern.
2. Define the rule's scope and contract: property or class level, supported
   input types, null/blank behavior, groups, violation message and parameters,
   error code, and any service or repository lookup. Ask before choosing
   unspecified validation semantics that change accepted input or API errors.
3. Implement the constraint as a `Constraint` subclass in
   `App\Validator\Constraints`. Declare its PHP attribute target to match the
   intended scope. For class-level validation, implement `getTargets()` as
   needed. Define stable message and error-code constants and map the code in
   `ERROR_NAMES`, following current local conventions. If the constraint has
   options, use a typed constructor compatible with Symfony's base constructor;
   preserve options, groups, and payload behavior.
4. Implement the matching `*Validator` as a `ConstraintValidator` in the same
   namespace. Inject required collaborators through the constructor and use
   the existing `App\` autowiring/autoconfiguration rather than manual
   instantiation or redundant service tags. Verify Symfony's validator
   discovery and naming behavior when using a non-default validator class.
5. Keep validation focused on the rule. Define deliberately how `null` or
   blank values are handled; optionality and requiredness usually belong to
   separate constraints such as `NotBlank`. Validate supported value types
   explicitly: throw Symfony's `UnexpectedTypeException` for the wrong
   constraint and `UnexpectedValueException` for incompatible values when
   that is the intended contract. Do not silently cast, normalize, or accept
   malformed input unless the rule explicitly defines that behavior.
6. Add violations through the validator context using the declared message,
   parameters, and error code. Avoid leaking secrets or unnecessary personal
   or persisted data through violation messages, logs, or parameters. Surface
   collaborator failures according to the repository's error-handling
   conventions; do not convert infrastructure failures into a valid result or
   a misleading validation violation.
7. Add tests for valid and invalid values, null/blank and unsupported types
   where relevant, and the exact violation message/code/parameters. If the
   validator uses a repository or service, test the rule's observable behavior
   with controlled collaborator responses. Also verify the constraint through
   Symfony's configured Validator service when service discovery, constraint
   mapping, or dependency injection matters; direct calls to `validateInContext`
   alone do not prove framework wiring.
8. For DTO usage, place the constraint on the correct field or class and
   preserve operation-specific validation groups and create/update/patch
   behavior. Follow [add-api-dto-mapping](../add-api-dto-mapping/SKILL.md) for
   request mapping, DTO validation, and HTTP-boundary coverage when the API
   contract changes. Keep entity validation separate from request DTO
   validation unless the existing domain behavior requires both.
9. Update API error documentation or other existing documentation when the
   externally observable validation contract changes. Do not add a migration
   unless persisted entity structure changes; follow the entity skill when it
   does.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run the focused test under `tests/Integration/Validator/Constraints/` inside
  the PHP development container, following shared guidance.
* Confirm the configured Validator reports the expected violation for an
  invalid value and no violation for a valid value. Check actual validator
  service resolution when the constraint or validator depends on autowiring.
* When attached to a DTO or endpoint, run the relevant DTO and caller-boundary
  tests as described in
  [add-api-dto-mapping](../add-api-dto-mapping/SKILL.md).
* Run relevant PHP quality checks from shared guidance.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
