---
name: update-localization
description: Change supported languages and locales, request locale selection, translation catalogs, user localization preferences, and timezone behavior with consistent defaults, persistence, and fallback verification.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when changing supported languages/locales, request locale
selection, translation resources, user localization preferences, or timezone
lists and validation. Translation language, formatting locale, user timezone,
and storage timezone are separate contracts; changing one does not implicitly
change the others.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md). Use
[update-application-configuration](../update-application-configuration/SKILL.md)
for settings and wiring, and
[add-api-dto-mapping](../add-api-dto-mapping/SKILL.md) when preference inputs
or request mappings change.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Localization path map](#localization-path-map)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Localization path map [ᐞ](#table-of-contents)

<a id="localization-path-map"></a>

Inspect the affected source, configuration, and consumers:

* `src/Enum/Language.php` and `Locale.php`: supported values and enum defaults.
* `src/Service/Localization.php`: supported lists, request-locale fallback,
  default user timezone, timezone formatting, caching, and failure logging.
* `src/EventSubscriber/AcceptLanguageSubscriber.php`: early request locale
  selection using the configured `%locale%`.
* `config/services.yaml`, `config/packages/framework.yaml`, and
  `translation.yaml`: default locale, translator resource path and fallback.
* `translations/`, currently including `security+intl-icu.fi.xlf`, and
  `src/Security/Handler/TranslatedAuthenticationFailureHandler.php`:
  actual message keys, domains, formats, and authentication translation.
* `src/Controller/v1/Localization/` and its integration/E2E tests:
  language, locale, and timezone list contracts and OpenAPI metadata.
* `src/Entity/User.php`, `src/DTO/User/User.php`,
  `src/AutoMapper/User/RequestMapper.php`, `src/Security/SecurityUser.php`,
  and `src/EventSubscriber/JWTCreatedSubscriber.php`: preference defaults,
  input conversion, persisted values, security-user snapshots, and JWT claims.
* Language, locale, and timezone constraints under `src/Validator/Constraints/`,
  and enum types under `src/Doctrine/DBAL/Types/`: accepted inputs and database
  representation. Shared log entities use UTC independently of user timezone.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Define the requested change and affected boundaries: supported values,
   defaults, missing/unsupported input, message fallback, preference updates,
   public lists, JWT claims, or timezone presentation. Ask before changing
   language negotiation policy, persisted enum values, preference precedence,
   timezone interpretation, or existing-user data. Do not equate adding a
   translated catalog with adding a supported language or locale.
2. Trace request locale selection before changing negotiation.
   `AcceptLanguageSubscriber` runs on `RequestEvent` at priority 100 and
   compares the entire `Accept-Language` header against `Language::getValues()`
   with strict matching. Missing/unsupported headers use `%locale%`; weighted
   lists, regional tags, and case variants are not parsed or normalized.
   Preserve this behavior unless a negotiation change is approved. Specify
   precedence and fallback for those cases before adopting a parser.
   Inspect request type and event ordering; do not assume there is a
   main-request guard or that setting the locale late updates earlier messages.
3. Keep defaults and sources distinct. `Localization::getRequestLocale()`
   checks the current request locale against `Locale` values and otherwise
   returns the enum default, including when there is no request. Translator
   defaults/fallbacks use `%locale%`, while user/entity/DTO defaults use enums
   and `Localization::DEFAULT_TIMEZONE`. Stored preferences and JWT claims
   do not by themselves establish the locale of the current HTTP request.
   Trace actual consumers rather than inventing user/header precedence.
4. For supported-value changes, update the appropriate enum, list consumers,
   validation, mapper conversions, defaults, fixtures, and metadata within
   scope. Language and locale are different enums even where their values
   currently coincide. `EnumLanguageType` and `EnumLocaleType` use `EnumType`,
   whose SQL declaration is a database `ENUM`; additions/removals can therefore
   require a schema migration even without editing an entity attribute.
   Follow [add-doctrine-entity](../add-doctrine-entity/SKILL.md) to generate and
   review it. Renames/removals also need an approved plan for existing values;
   never silently coerce stored preferences to make hydration succeed.
5. Keep translation catalogs aligned with the actual domain and call site.
   Preserve XLIFF structure, source keys, target locale, placeholders, and
   ICU formatting semantics. Do not translate machine-readable identifiers,
   exception codes, or API field names. Check missing keys and fallback using
   the real translator; a catalog file alone does not prove it is loaded.
   `TranslatedAuthenticationFailureHandler` translates `Invalid credentials.`
   in the `security` domain and dispatches the authentication-failure event.
   Preserve that event and generic credential message; translation must not
   reveal whether a username exists or bypass failure/lockout logging.
6. For preference changes, preserve create/update/patch and visited-field
   semantics through the DTO and mapper. Verify language/locale enum conversion
   and timezone validation separately. Trace security-user values and newly
   issued JWT localization claims; do not assume editing a preference updates
   an already issued token. Use
   [implement-api-authentication](../implement-api-authentication/SKILL.md)
   when token or authentication behavior changes.
7. For timezones, retain identifiers as stable machine values and distinguish
   them from display labels and offsets. `getFormattedTimezones()` builds
   `timezone`, `identifier`, `offset`, and `value` using PHP timezone data and
   the current instant. Verify negative and fractional offsets, daylight
   saving, and label formatting when affected; a count-only test is not enough.
   Do not treat a current offset as a permanent property of a zone.
8. Account for cache behavior and validation coupling. `getTimezones()` caches
   under `application_timezone` for one year and logs failures while returning
   its existing empty result; `TimezoneValidator` consumes that cached list.
   Current-instant offsets may outlive a seasonal transition in cached output.
   If freshness, cache keys, lifetime, or failure behavior changes, define and
   test hit/miss/failure behavior and validation effects. Do not silently hide
   cache failures, broadly clear shared caches, or change process-wide/default
   storage timezone to fix a display issue.
9. Keep localization endpoint status, array shapes, allowed methods, and API
   examples aligned with actual lists. Do not introduce frontend assets or a
   parallel translation service. Follow
   [add-event-subscriber](../add-event-subscriber/SKILL.md) when locale event
   registration changes and
   [update-api-response-contract](../update-api-response-contract/SKILL.md)
   when shared output behavior changes.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Follow [write-tests](../write-tests/SKILL.md) for all test changes. Start with
  `tests/Integration/Service/LocalizationTest.php`,
  `tests/Integration/EventSubscriber/AcceptLanguageSubscriberTest.php`, and
  affected validator, mapper, DBAL-type, JWT, or controller tests.
* For negotiation/fallback changes, cover supported and unsupported headers,
  missing/empty values, weighted lists, regional/case variants, configured
  defaults, no current request, and invalid request locales as affected.
  Exercise the real HTTP route and translator ordering, not only a manually
  constructed subscriber.
* For translation changes, check catalog syntax with available Symfony tooling
  after inspecting its supported commands/options inside the PHP development
  container. Verify actual translated output, domain, placeholders, and
  fallback through the caller. The mock in
  `tests/Integration/Security/Handler/TranslatedAuthenticationFailureHandlerTest.php`
  verifies a translation call, not catalog loading or HTTP locale selection.
* For timezone changes, assert representative identifiers, exact output shape,
  offset formatting, seasonal/fractional cases, cache behavior, and validator
  results. Keep time-sensitive expectations deterministic. The E2E tests in
  `tests/E2E/Controller/v1/Localization/` cover endpoint contracts, not all
  offset calculations or fallback paths.
* Verify preference persistence and newly issued JWT claims when affected,
  plus existing-row compatibility and migration/schema checks for enum changes.
  Apply shared guidance for PHP quality and configuration checks. Do not run
  migrations against production or rewrite existing preferences without approval.
* For this skill's Markdown alone, run from the repository root on the Docker
  host with development services running:

  ```bash
  make lint-markdown
  make check-skills
  ```

* Report documentation checks separately from runtime localization evidence.
  Adding this skill does not add languages, update catalogs, change timezone
  behavior, or prove that its described HTTP scenarios passed.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
