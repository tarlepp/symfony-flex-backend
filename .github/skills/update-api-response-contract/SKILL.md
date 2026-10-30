---
name: update-api-response-contract
description: Change shared API response serialization, population groups, format selection, headers, and error envelopes while preserving endpoint contracts and sensitive-field exclusions.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when changing response behavior shared across endpoints:
serialization contexts and population groups, output formats, response headers,
status handling, or error envelopes. For endpoint-specific behavior, also use
[add-custom-api-endpoint](../add-custom-api-endpoint/SKILL.md) or
[add-rest-resource](../add-rest-resource/SKILL.md). For request DTOs and input
mapping, use [add-api-dto-mapping](../add-api-dto-mapping/SKILL.md); an input DTO
change does not automatically change response exposure.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Response path map](#response-path-map)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Response path map [ᐞ](#table-of-contents)

<a id="response-path-map"></a>

Inspect the affected producers and consumers before editing:

* `src/Rest/ResponseHandler.php` and its interface: serializer context,
  format selection, status, content type, and serialization failures.
* `src/Rest/Traits/Methods/`, `RestMethodHelper`, and `RestResource`:
  operation-specific data/status, exception conversion, associations, and
  resource serializer-context overrides.
* `src/Entity/` attributes and `Set.*` constants, including `User` and shared
  entity traits: actual serialized fields and relationship groups. Generic
  REST methods currently pass resource results to the serializer; do not
  assume request DTOs define their output or add a new response layer merely
  to change one field.
* `src/Controller/`: custom response producers. Some use the response handler,
  others serialize with explicit groups and `JsonResponse(json: true)`, and
  others return data directly through `JsonResponse`.
* `src/EventSubscriber/ResponseSubscriber.php` and
  `ExceptionSubscriber.php`: API version header, exception status, logging,
  production-safe messages, and development-only debug details.
* `config/packages/framework.yaml`, `nelmio_cors.yaml`,
  `nelmio_api_doc.yaml`, and `config/services.yaml`: serializer setup,
  browser-visible headers, documentation areas, and wiring.
* `tests/Integration/Rest/ResponseHandlerTest.php`, response/exception
  subscriber tests in `tests/Integration/EventSubscriber/` and
  `tests/Unit/EventSubscriber/`, plus affected HTTP tests in
  `tests/E2E/Controller/`: existing contract evidence and regression helpers.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Define the affected routes, operations, identities, formats, and environments.
   Record the existing status, headers, body shape, field types, null/empty
   behavior, and error output. Ask before changing compatibility, default
   exposure, negotiation policy, or public error semantics. Do not silently
   introduce a universal response envelope or normalize all custom endpoints.
2. Trace the actual response producer through serialization and kernel
   subscribers. Shared handler changes do not cover direct `JsonResponse`
   callers, authentication-bundle failures, or every exception path. Inspect
   security configuration and authentication handlers when those failures are
   in scope; do not assume they use `ExceptionSubscriber`'s envelope.
3. Preserve population semantics unless explicitly changing them. The handler
   reads `populate` from query parameters before request parameters and builds
   `default` groups without a resource, or the entity's short-name group with
   one. With a resource, query-key presence enables `populateAll` and
   `populateOnly`; it is not a boolean-value check. `populateAll` adds
   association groups only when explicit population is empty. `populateOnly`
   or a `Set.*` group replaces the base groups, retaining the entity group
   when population is empty. Verify precedence, duplicates, empty values,
   combinations, and resource overrides rather than simplifying these rules
   incidentally. Resource context is merged after generated groups and may
   override them; an explicit `createResponse()` context bypasses generation.
4. Review every field reachable through changed groups, including nested
   associations and shared traits. Population is not authorization. Preserve
   endpoint/voter checks and sensitive-field exclusions for each caller
   identity; never add wildcard groups or disable group filtering to fix a
   missing field. Test credential and privileged-field absence, including
   nested output, not merely the presence of the intended field. Consider
   association cycles and unexpectedly expanded payloads.
5. Treat format selection as an existing contract, not conventional `Accept`
   negotiation. The handler currently honors an explicit format first,
   otherwise selects XML from the request's content-type format, falling back
   to JSON; it does not select output from `Accept`. Serialization failures
   become HTTP 400 exceptions. If negotiation changes are approved, specify
   header precedence, supported formats, missing/unsupported values, fallback
   or rejection behavior, response content type, and cache variation where
   applicable. Exception responses are currently JSON independently of the
   successful response format.
6. Preserve operation-specific status and body behavior. Inspect create,
   find, count, ids, update, patch, delete, and affected custom callers instead
   of imposing generic REST expectations. Distinguish serializer-produced
   JSON strings from raw data passed to `JsonResponse`; preserve `json: true`
   where appropriate to avoid double encoding. Keep array/object, scalar,
   empty-result, and null shapes explicit.
7. For error changes, trace `RestMethodHelper`'s exception conversion before
   `ExceptionSubscriber`. Its current envelope contains `message`, `code`,
   and HTTP `status`, with `debug` only in `dev`. Preserve logging and
   production masking of database/internal details, client-safe messages,
   and authenticated versus unauthenticated access-denied status behavior.
   Do not assume exception code equals HTTP status or that exception headers
   survive every layer; verify required headers through the full HTTP path.
8. For header changes, inspect response-event registration and priority,
   success and failure responses, custom controllers, and relevant request
   types. `ResponseSubscriber` adds `X-API-VERSION`; browser exposure is
   separately configured in Nelmio CORS. Keep permitted origins and headers
   restricted; do not broaden CORS to make a header visible. Check preflight
   and cross-origin responses when browser behavior is affected.
9. Update affected OpenAPI attributes, model groups, schemas, statuses, media
   types, headers, and examples alongside the implementation. Inspect the
   configured documentation area so the affected routes are actually included.
   Reuse existing metadata rather than creating a parallel specification.
   Use [update-api-documentation](../update-api-documentation/SKILL.md) for
   schema, security-description, and generated-area verification.
   Follow [write-tests](../write-tests/SKILL.md) for all test changes and
   [add-event-subscriber](../add-event-subscriber/SKILL.md) when event
   registration or ordering changes.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run focused handler/subscriber tests and affected E2E HTTP tests in the PHP
  development container using the testing skill. Direct event or handler
  calls do not prove configured routing, security handlers, subscriber order,
  or final response headers.
* Assert exact decoded body shape, field types and exclusions, HTTP status,
  content type, and affected headers. Cover representative collection,
  single-item, write, custom, and failure paths for a shared change. Exercise
  explicit/default formats, population combinations, resource/context
  overrides, empty data, serialization failures, and relevant caller identities
  where affected. Test non-development error masking separately from `dev`
  debug output; a passing test in one environment does not establish both.
* For OpenAPI changes, inspect the generated `/api/doc.json` response in the
  relevant area against actual output. `tests/E2E/DocumentationTest.php`
  checks availability, not schema accuracy. PHP lint and a documentation
  endpoint returning 200 do not verify response-contract agreement.
* Apply shared guidance for service/configuration checks and PHP quality
  checks when implementation changes. For this skill's Markdown alone, run
  these commands from the repository root on the Docker host with the
  development services running:

  ```bash
  make lint-markdown
  make check-skills
  ```

* Report documentation validation separately from runtime contract evidence.
  Adding or editing this skill does not mean application responses changed
  or its described HTTP scenarios were exercised.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
