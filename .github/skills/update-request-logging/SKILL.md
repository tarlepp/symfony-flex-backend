---
name: update-request-logging
description: Change request and login logging, redaction, stored metadata, subscriber routing, and logging configuration while protecting sensitive data and preserving persistence and authentication behavior.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when changing request or login audit records, redaction,
ignored paths, identity attribution, or their interaction with Monolog and
application configuration. Database audit records and Monolog output are
separate sinks; changing one does not automatically protect the other.
This skill does not authorize collecting more sensitive data or changing
authentication, account lockout, or retention policy.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md). Use
[update-application-configuration](../update-application-configuration/SKILL.md)
for settings and wiring, and
[implement-api-authentication](../implement-api-authentication/SKILL.md)
when authentication behavior changes.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Logging path map](#logging-path-map)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Logging path map [ᐞ](#table-of-contents)

<a id="logging-path-map"></a>

Trace the affected data from its source to every stored or emitted copy:

* `src/EventSubscriber/RequestLogSubscriber.php` and
  `src/Utils/RequestLogger.php`: termination-time filtering, security identity,
  request/response capture, persistence isolation, and failure reporting.
* `src/Entity/LogRequest.php`,
  `src/Entity/Traits/LogRequestProcessRequestTrait.php`, and
  `LogEntityTrait.php`: stored headers, parameters, raw content, URL metadata,
  status, response-content length, client metadata, and redaction.
* `src/Utils/LoginLogger.php`, `src/Entity/LogLogin.php`, and
  `src/Enum/LogLogin.php`: login outcome, optional user, request-stack lookup,
  device parsing, and stored client metadata.
* `AuthenticationSuccessSubscriber`, `AuthenticationFailureSubscriber`, and
  `LockedUserSubscriber` in `src/EventSubscriber/`, plus
  `src/Entity/LogLoginFailure.php`: authentication event registration,
  priority, audit entries, and the separate failure records used for lockout.
* Corresponding resources and repositories in `src/Resource/` and
  `src/Repository/`: save/flush behavior, associations, and log consumers.
* `config/packages/monolog.yaml` and `ExceptionSubscriber`: environment-specific
  diagnostic sinks, buffering, formatting, exclusions, and exception logging.
* `REQUEST_LOG_SENSITIVE_PROPERTIES` and `REQUEST_LOG_IGNORED_ROUTES` in
  tracked application JSON defaults and test configuration, their direct
  `#[Autowire]` consumers, and the `APPLICATION_CONFIG` selection. Follow the
  [configuration guide](../../../doc/CUSTOM_CONFIGURATION.md) without
  printing resolved secrets or reading private configuration unnecessarily.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Establish which records, sinks, request types, identities, and environments
   change. Define the necessary fields, exclusions, redaction marker, failure
   behavior, and compatibility requirements. Ask before expanding collected
   data, changing ignored paths, retention/deletion, or access to stored logs.
   Use synthetic values for investigation and tests; never copy real
   credentials, tokens, cookies, or personal request data into fixtures,
   command output, snapshots, or documentation.
2. Inspect each stored representation independently. The current request log
   stores headers, parsed parameters, raw request content, URI/request-URI
   metadata, and client information. Response capture records status and
   content length, not the response body. Sanitized parameters do not prove
   that content, URLs, client metadata, exception messages, or Monolog context
   are safe. Do not start logging response bodies to implement metadata changes.
3. Keep redaction at the shared capture boundary rather than duplicating it
   in controllers. Review `cleanParameters()` and `cleanContent()` separately:
   structured keys are lowercased before matching configured names and arrays
   are traversed recursively, while raw content uses a separate regex-based
   transformation. Do not describe that transformation as a general JSON,
   form, XML, URL, or multipart sanitizer. Verify actual coverage before
   claiming that a configured property is protected in every representation.
   When changing redaction, preserve non-sensitive data and explicit handling
   of malformed input; do not fall back to storing unredacted input on errors.
4. Define and test supported payloads and key matching. Include nested maps
   and lists, mixed-case keys, repeated fields, empty/null/non-string values,
   escaped and multiline strings, and adjacent non-sensitive fields where
   relevant. Trace JSON decoding, form parsing fallback, raw content, headers,
   query strings, and URLs independently. Do not broaden parsing or silently
   discard data to make a sanitizer appear successful. Preserve the original
   request for downstream consumers; sanitize captured copies.
5. Preserve filtering semantics unless explicitly changing them.
   `RequestLogSubscriber` handles `TerminateEvent` at priority 15 and skips
   real `OPTIONS` methods, paths containing `/_profiler`, exact ignored paths,
   and configured `/*` matches. These are path checks, not route-name checks;
   the current wildcard implementation uses substring matching rather than
   a router glob. Cover near-matching paths before changing that behavior.
   Ignoring request records does not disable login auditing or Monolog output.
6. Preserve attribution and request lifecycle behavior. Trace `SecurityUser`,
   `ApiKeyUser`, and anonymous requests through `UserTypeIdentification`;
   store entity identifiers, not credentials. The logger services hold mutable
   state, so exercise sequential requests with different identities and
   anonymous callers when changing service lifecycle or attribution. Check
   main-request flags and actual termination dispatch, not just direct calls
   to `handle()`.
7. Preserve persistence isolation. `RequestLogger` clears its entity manager
   before resolving user/API-key references and saving the audit entry to
   avoid flushing unrelated pending entities. Do not remove that safeguard
   or introduce additional flushes without verifying the surrounding unit of
   work. Its missing-request/response guard and catch-and-log persistence
   failure behavior differ from `LoginLogger`, which throws when the request
   stack is empty and lets save failures propagate. Changes to these contracts
   need explicit decisions and tests; never add silent failure suppression.
   Ensure diagnostic failure messages do not reintroduce captured secrets.
8. Keep login audit outcomes separate from lockout accounting. Trace Lexik
   event names and class registrations, priorities, user lookup, nullable
   identity, and `LogLogin::SUCCESS`/`FAILURE`. `LockedUserSubscriber` maintains
   `LogLoginFailure` entries and checks/reset behavior independently of
   `LogLogin`. A logging change must not bypass lockout, alter thresholds,
   duplicate audit records, or turn failed authentication into success.
9. For Monolog changes, inspect `when@dev`, `when@test`, and `when@prod`
   handlers, channels, destinations, levels, buffering, formatters, and HTTP
   exclusions. Do not assume database redaction sanitizes exception strings
   or structured logger context. Preserve diagnostic visibility without
   dumping requests or configuration, disabling logging, or weakening access
   controls to troubleshoot a failure.
10. Update safe tracked defaults and tests together when settings change;
    preserve private overrides using the configuration skill. Document required
    operator action without overwriting local files. For changed persisted
    mappings, use [add-doctrine-entity](../add-doctrine-entity/SKILL.md).
    Review serializer groups and nested log associations for exposure, using
    [update-api-response-contract](../update-api-response-contract/SKILL.md)
    when output changes. New redaction does not sanitize historical rows;
    surface any required cleanup or backfill as separate approved work.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Follow [write-tests](../write-tests/SKILL.md) for all test changes. Start with
  `tests/Integration/Entity/LogRequestTest.php`,
  `tests/Integration/Utils/RequestLoggerTest.php`, and
  `tests/Integration/Utils/LoginLoggerTest.php`, then relevant subscriber,
  entity, resource, and repository tests. Existing mock save expectations
  alone do not prove redaction or persisted contents.
* For capture/redaction changes, assert synthetic secret absence in every
  affected persisted field and diagnostic sink, the intended replacement
  marker, and preservation of non-sensitive neighbors. Verify the configured
  consumer as well as a manually constructed entity. Include malformed input
  and a logging failure without exposing the original payload in test output.
* Exercise affected HTTP paths through termination and inspect stored records:
  expected count, attribution, main-request flag, metadata, redaction, and
  ignored paths. `tests/E2E/Controller/HealthzControllerTest.php` demonstrates
  asserting that an ignored endpoint creates no request log. Direct event
  calls do not prove the configured lifecycle runs.
* For login-related changes, use affected authentication subscriber tests and
  `tests/E2E/Controller/v1/Auth/LoginFailureTest.php` to verify failure records,
  success reset, and lockout behavior. Confirm login audit outcomes and record
  counts separately; lockout tests do not establish `LogLogin` correctness.
* For persistence changes, verify that logging does not flush unrelated pending
  entity changes and that failure reporting preserves the approved request or
  authentication outcome. For subscriber registration or priority changes,
  also use [add-event-subscriber](../add-event-subscriber/SKILL.md).
* Apply shared guidance and the configuration skill for PHP quality, container,
  JSON, and YAML checks when implementation/configuration changes. For this
  skill's Markdown alone, run from the repository root on the Docker host with
  development services running:

  ```bash
  make lint-markdown
  make check-skills
  ```

* Report documentation checks separately from executed logging scenarios.
  Adding this skill does not mean audit behavior changed, existing stored data
  was cleaned, or the described runtime checks were run.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
