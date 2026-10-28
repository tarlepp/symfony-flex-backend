---
name: update-php-runtime
description: Update the supported PHP runtime baseline consistently across Composer, Docker, CI, and documentation.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when changing the PHP version supported by the application or
the PHP runtime version used by the development, production, and CI workflows.
For Composer package updates that do not change the PHP baseline, use
[update-dependencies](../update-dependencies/SKILL.md). For PHP image tag or
digest refreshes that keep the supported PHP version unchanged, use
[update-docker-images](../update-docker-images/SKILL.md).

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

1. Confirm the requested target PHP version and scope. Check the current PHP
   requirement and `config.platform.php` in the root `composer.json` and every
   `tools/*/composer.json`, along with their lockfiles. Ask before changing the
   minimum supported PHP version, crossing a PHP major/minor line, or widening
   dependency constraints when the request does not specify the intended
   compatibility policy.
2. Check package and extension compatibility for the target PHP version,
   including root and tool Composer dependencies, Symfony and PHPUnit support,
   required PHP extensions, and project PHP configuration in `Dockerfile`,
   `docker/php/php.ini`, and `docker/php/php-dev.ini`. Review upstream release
   notes and security advisories. Do not assume that changing the container
   image alone changes Composer's platform requirement.
3. Keep the runtime baseline aligned across the project:
   * Update the root and `tools/*/composer.json` PHP requirements and
     `config.platform.php` values to the approved baseline.
   * Refresh each affected lockfile with Composer in the development
     container. Do not hand-edit lockfiles or use a broad dependency update
     unless required and approved.
   * Preserve the different purposes of version fields: `require.php` defines
     the supported constraint, `config.platform.php` simulates the Composer
     platform, and the PHP image uses a concrete patch tag shared by local
     development and CI.
   * Update the PHP base image tag and digest in `Dockerfile`, following
     [update-docker-images](../update-docker-images/SKILL.md) for multi-platform
     digest resolution and pin verification.
   * Review `.github/actions/php-bootstrap/action.yml` and
     `docker/ci-compose.yaml`. CI uses the Compose development image rather
     than selecting a separate runner PHP version; keep that shared runtime.
   * Search documentation and automation for explicit PHP-version statements,
     including `doc/DEVELOPMENT.md` and
     `doc/INSTALLATION_WITHOUT_DOCKER.md`; update statements that describe the
     supported baseline. Avoid copying version numbers into documents that can
     link to the Composer source of truth instead.
4. Review the full diff for accidental dependency changes, lockfile churn,
   inconsistent platform values, and outdated examples. Keep a baseline
   migration coherent across root, tools, runtime image, CI, and documentation;
   do not mechanically apply the tool-first commit sequence for unrelated
   Composer updates when it would leave PHP platform definitions inconsistent.
5. Do not recreate running services, remove volumes, deploy images, or change
   production data as part of validation without explicit approval.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run `make composer-validate` for the root Composer manifest and
  `composer validate --no-check-version` in every changed tool manifest
  directory. Confirm each lockfile is consistent with its manifest.
* Run `make check-security` when Composer dependencies change, plus relevant
  static-analysis and application test checks. Use `make run-tests-php` to
  reproduce CI's application test runner.
* When the PHP base image changes, run `make docker-image-verify-pins`, validate
  affected Compose files with `docker compose config --quiet`, and build both
  PHP `development` and `production` targets. Follow
  [update-docker-images](../update-docker-images/SKILL.md) for platform,
  tag/digest parity, and build details.
* Report unavailable platform builds or other validation blockers explicitly.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
