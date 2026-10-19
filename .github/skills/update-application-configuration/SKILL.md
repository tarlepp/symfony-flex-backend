---
name: update-application-configuration
description: Update Symfony service wiring, environment-backed settings, and custom JSON application configuration using this repository's configuration and validation conventions.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding or changing application settings, Symfony package
configuration, dependency-injection wiring, or environment-specific overrides.
It covers configuration changes, not dependency upgrades or Docker image
updates; use the corresponding repository skills for those tasks.

For updates to installed Symfony Flex recipes, follow
[update-symfony-recipes](../update-symfony-recipes/SKILL.md) for the Composer
update workflow,
then use this skill to integrate generated configuration changes.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md). Read
[CUSTOM_CONFIGURATION.md](../../../doc/CUSTOM_CONFIGURATION.md) for the existing
local-configuration workflow.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Read `composer.json` for available packages and supported versions, and
   `symfony.lock` for recipes. Inspect the affected configuration, its service
   consumers, and nearby tests. Trace the setting from its source through env
   processors, parameters, and dependency injection to its actual use. Prefer
   the implementation over outdated documentation or assumed Symfony defaults.
2. Establish the setting's name, type, requiredness, default, intended
   environments, and effect. Ask before changing unspecified defaults,
   authentication, CORS, trusted proxies, persistence, or other access and
   deployment boundaries. Distinguish an absent value from an empty string,
   `null`, zero, or `false`; do not add a silent fallback for required or
   malformed configuration.
3. Preserve the custom configuration flow: `APPLICATION_CONFIG` identifies a
   JSON file, `app.config` exposes its decoded contents, and
   `config/services.yaml` defines env defaults extracted from individual JSON
   keys. Some consumers, such as `RequestLogger` and `RequestLogSubscriber`,
   inject individual keys directly with `#[Autowire]`. Follow the closest
   existing pattern rather than injecting the whole configuration array or
   manually reading files and environment variables in application services.
4. Use appropriate Symfony env processors and typed constructor arguments.
   Keep JSON arrays as arrays and apply scalar conversion deliberately.
   Inspect aliases such as `JWT_PRIVATE_KEY_PATH` sourcing `JWT_SECRET_KEY`;
   changing one name does not automatically update the other. Check real
   environment overrides as well as JSON-backed env defaults. Preserve
   container-resolved paths and the intended runtime versus compile-time
   resolution; do not add `resolve:` or hard-coded host paths indiscriminately.
5. For new settings, update safe tracked defaults and all relevant consumers,
   fixtures, and deployment examples within scope. Check `.env`,
   `config/packages/`, `when@dev`, `when@test`, `when@prod`, `phpunit.xml.dist`,
   and Compose configuration where applicable. Do not assume development
   configuration is loaded in tests or production, or that a host file is
   available at the same container path. Do not commit real credentials, JWT
   keys, `.env.local`, or ignored `secrets/application.*.json` files.
6. Preserve local overrides. `make local-configuration` copies the default
   files into `.env.local` and `secrets/application.local.json`; it can
   overwrite existing files, so do not run it blindly on an established
   checkout. Do not edit a developer's private configuration merely to make a
   new required key pass validation. Document the required local/deployment
   update and use isolated test configuration when needed. Avoid commands such
   as `make configuration` or resolved configuration dumps when they would
   expose secrets in output.
7. For service wiring, follow `config/services.yaml`: normal `App\` discovery
   uses autowiring and autoconfiguration, while excluded directories such as
   `src/EventListener/` need their established explicit registration. Prefer
   typed constructor injection and existing aliases or attributes. Do not add
   redundant tags, make application services public for tests, or introduce
   service-locator access to bypass a missing dependency. Preserve the
   environment-specific service definitions and test visibility conventions.
8. Account for compiler passes, tags, priorities, aliases, lazy services, and
   decorators when changing wiring. `StopwatchCompilerPass` is registered only
   in development and decorates selected services; successful test-container
   boot alone does not prove development wiring works. Do not mark services
   readonly or bypass decorators to hide compatibility problems. For listener,
   subscriber, voter, or request-mapping changes, follow the relevant skill
   for registration and caller-boundary checks.
9. Keep Symfony package configuration aligned with the installed component's
   supported options. If a justified new package is needed, use Composer/Flex
   and review its generated recipe changes rather than hand-writing bundle
   registration or assuming the component exists. Preserve existing security
   guards and logging redaction; do not disable validation, logging, or a
   protection mechanism to make container compilation succeed.
10. Follow [write-tests](../write-tests/SKILL.md) for behavior changes. Exercise
    the real configured consumer, not just a manually constructed service with
    hard-coded arguments. Cover the intended default and override behavior,
    relevant missing or malformed values, and affected environments. For
    wiring changes, verify the selected implementation and actual caller
    behavior so a valid but unused service definition cannot pass unnoticed.
    Reuse existing consumer tests such as
    `tests/Integration/Utils/RequestLoggerTest.php` and
    `tests/Integration/EventSubscriber/RequestLogSubscriberTest.php` where
    relevant; do not weaken redaction or authorization coverage.
11. Update [CUSTOM_CONFIGURATION.md](../../../doc/CUSTOM_CONFIGURATION.md)
    and other affected setup or command
    documentation when keys, types, defaults, file locations, or deployment
    steps change. State any required operator action without applying
    production changes, restarting services, or overwriting local files
    without explicit approval.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run `make lint-yaml` for YAML changes and `make lint-configuration` for the
  active application JSON file, inside the development container or through
  container-aware Make targets. JSON syntax validation does not verify required
  keys, types, env precedence, or consumer behavior; validate those separately.
  If editing a different configuration file, verify that file too rather than
  assuming the active-file check covers it.
* For service or package configuration changes, run
  `bin/console lint:container` and boot or test the affected environments.
  Env values may resolve lazily: inspect the installed command's support for
  `--resolve-env-vars` when required, and exercise the consumer to verify
  runtime resolution. Do not print resolved secret values.
* Run targeted consumer and caller-boundary tests plus relevant checks from
  shared guidance. For Compose changes, validate with
  `docker compose config --quiet` from the host, including the relevant
  production overrides when affected. Do not recreate services to validate
  configuration without approval.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
