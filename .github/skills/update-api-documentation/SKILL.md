---
name: update-api-documentation
description: Maintain generated OpenAPI documentation, operation metadata, model schemas, reusable responses, and security descriptions independently of endpoint implementation, verifying configured areas against actual API behavior.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when updating or repairing API documentation without necessarily
adding an endpoint: OpenAPI attributes, model schemas, response components,
security descriptions, examples, or documentation areas. It owns documentation
accuracy, not permission to change runtime contracts to match a specification.
For endpoint changes, also use
[add-custom-api-endpoint](../add-custom-api-endpoint/SKILL.md) or
[add-rest-resource](../add-rest-resource/SKILL.md). For shared output changes,
use [update-api-response-contract](../update-api-response-contract/SKILL.md).

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Documentation path map](#documentation-path-map)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Documentation path map [ᐞ](#table-of-contents)

<a id="documentation-path-map"></a>

Inspect the actual generation inputs and runtime evidence:

* `composer.json`, `composer.lock`, and `symfony.lock`: installed Nelmio,
  swagger-php, Symfony components, and recipe configuration. Use supported
  OpenAPI attributes, not remembered annotations or API Platform conventions.
* `config/packages/nelmio_api_doc.yaml`: global metadata, security declarations,
  and `default`/`internal` area path filters.
* `config/routes/nelmio_api_doc.yaml`: `/api/doc` UI and `/api/doc.json` JSON
  routes. An area definition does not by itself expose a separate HTTP route.
* OpenAPI attributes in `src/Controller/`, including `HealthzController`,
  `v1/Auth/GetTokenController`, and custom profile/relationship controllers:
  operation IDs, bodies, parameters, responses, tags, and Nelmio model groups.
* Entity serializer/OpenAPI attributes, request DTOs, AutoMapper configuration,
  resources, and REST action/method traits: accepted data, actual output,
  inherited routes, status codes, and field exposure.
* `config/packages/security.yaml`, authentication handlers, voters, and
  response/exception subscribers: real access policy, authentication errors,
  headers, and error bodies.
* `tests/E2E/DocumentationTest.php` and affected E2E controller tests:
  documentation availability versus actual API contracts.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Establish which operations, schemas, components, and areas are affected.
   Compare current generated documentation with source and HTTP evidence
   before editing. Decide whether the defect is in metadata or implementation;
   ask before changing public behavior, area exposure, or security policy.
   A documentation task must not weaken validation or authorization to make
   an inaccurate example work.
2. Verify inclusion and route discovery. The default area covers selected
   operational/auth/profile/localization paths; the internal area is broader.
   Check configured regexes against real paths, HTTP methods, class prefixes,
   inherited REST traits, and route requirements. Do not widen default filters
   just to see an internal operation or invent an internal-area URL. Preserve
   intentional audience separation and document the correct area.
3. Keep operation metadata aligned with the real caller boundary. Verify path
   versus query/header inputs, requiredness, types, enum values, defaults,
   nullability, and request-body media types. Distinguish omitted fields from
   explicit null and create/update/patch behavior using
   [add-api-dto-mapping](../add-api-dto-mapping/SKILL.md). Check operation IDs
   for uniqueness and avoid duplicate operations from overlapping attributes.
4. Describe responses from actual serializer contexts, not request DTO shape
   or entity properties alone. Match Nelmio `Model` groups to runtime groups,
   including `Set.*` and nested associations. Preserve sensitive-field
   exclusions. Specify array versus object/scalar, empty results, formats,
   status codes, and headers accurately. Do not claim `Accept` negotiation
   where the handler instead selects formats from request content type.
5. Trace error producers separately: generic REST exception conversion,
   `ExceptionSubscriber`, Lexik authentication, and custom controllers can
   have different envelopes and statuses. Describe actual required fields and
   safe examples, not one universal error schema by assumption. Keep
   development debug details out of public production examples.
6. Align security descriptions with actual firewalls, authenticators,
   attributes, and voters. Check every declared security requirement references
   a defined scheme in the generated document. Current configuration includes
   a global `Bearer` requirement and commented legacy `securityDefinitions`;
   do not assume those comments define a working OpenAPI component.
   Verify the generated OpenAPI version before choosing scheme syntax.
   Distinguish unauthenticated operations from operations inheriting global
   requirements, and alternatives from combined requirements. Document
   JWT/API-key transport without exposing real credentials or implying that
   an Authorization parameter enforces authentication.
7. Prefer existing attribute/model patterns. When introducing reusable
   schemas or responses, use the installed Nelmio/swagger-php mechanism and
   verify component discovery and every reference in each affected area.
   Do not assume a reusable component registry already exists, create a
   parallel specification, or extract unrelated repeated metadata merely for
   consistency. Preserve intentionally different responses and model groups.
8. Use synthetic examples with correct shape, format, enum values, and
   placeholder syntax. Do not copy tokens, private URLs, user records, or
   diagnostic traces into documentation. Do not translate machine-readable
   field names or introduce undocumented write-only inputs by showing
   serializer output as a request example.
9. Update the owning attributes/configuration and relevant contributor
   instructions, not generated output by hand. Inspect generation warnings
   and schema references rather than treating JSON serialization as semantic
   validation. Use the configuration skill for wiring/area settings and the
   response-contract skill for approved runtime changes.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

For executable metadata/configuration changes, generate each affected area
and inspect paths, methods, components, references, security, and examples.
The installed `nelmio:apidoc:dump` command supports area and format selection.
From the repository root on the Docker host with development services running:

```bash
docker compose exec -T php bin/console nelmio:apidoc:dump --area=default --format=json
docker compose exec -T php bin/console nelmio:apidoc:dump --area=internal --format=json
```

Select the affected command(s); these write the generated document to stdout.
Check current command help or installed implementation before adding flags.
If generation/help is blocked, report the failure rather than substituting an
empty document or claiming validation. Store any temporary output outside the
tracked specification and review it for sensitive examples before sharing.

Use [write-tests](../write-tests/SKILL.md) for changed tests. Assert affected
generated-document fields and relevant HTTP contracts, not only status 200.
`tests/E2E/DocumentationTest.php` currently checks UI/JSON availability, not
schema accuracy or the internal area. A successful dump does not prove that
examples match runtime output, security requirements resolve correctly, or
every documented operation is included in the intended area.

OpenAPI PHP attributes are executable metadata, not Markdown-only prose:
run affected syntax/style checks, generation, and focused regression checks
using [the validation guide](../../../doc/TESTING.md#selecting-validation).
Apply shared configuration/container checks when wiring changes. Do not add a
new validator dependency when existing tooling and focused assertions suffice.

For this skill's Markdown alone, run from the repository root on the Docker
host with development services running:

```bash
make lint-markdown
make check-skills
```

Report Markdown validation, generation/semantic inspection, and runtime evidence
separately. Adding this skill does not update API metadata or prove that
generated documentation and runtime responses agree.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
