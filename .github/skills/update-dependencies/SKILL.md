---
name: update-dependencies
description: Update Composer, Composer tool, Docker image, or GitHub Actions dependencies safely using this repository's update and validation workflows.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when updating or reviewing dependency versions, lockfiles, base
images, or pinned GitHub Actions. Do not use it to add unrelated dependencies;
follow the feature-specific skill and repository rules when a feature requires
a new package.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Update order](#update-order)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Update order [ᐞ](#table-of-contents)

<a id="update-order"></a>

For Composer dependency updates, update the tool dependencies under `tools/`
first. Validate and commit those changes separately before updating the main
application dependencies in the root `composer.json` and `composer.lock`.
Do not combine tool and application dependency updates in the same commit.
If commit authorization has not been given, ask for it and wait before
committing or starting the main dependency update. If no tool updates are
available, state that and proceed without an empty commit.

Standalone Symfony Flex recipe updates are configuration/scaffolding updates,
not Composer package upgrades; this tool-first ordering does not apply to them.
Follow [update-symfony-recipes](../update-symfony-recipes/SKILL.md) without
upgrading packages.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Identify the dependency scope and requested update range. Check
   `composer.json` and `composer.lock` for application dependencies,
   `tools/*/composer.json` and their lockfiles for Composer tools, Dockerfiles
   and Compose files for image versions, or workflow files for GitHub Actions.
   Read `symfony.lock` when Symfony recipes may be affected. If the scope or
   acceptable version range is unclear, ask before making a broad update.
2. Check the existing update procedures before changing versions:
   * Application Composer dependencies: use `make check-dependencies-patch`,
     `make check-dependencies-minor`, or `make check-dependencies-latest` to
     inspect available updates. Use `make update` only for an explicitly
     requested broad update; for a focused update, run Composer's package-level
     update command inside the PHP development container.
   * Composer tool dependencies: inspect the relevant `tools/*/composer.json`
     and lockfile, and use `make update-tools` only when the requested scope
     includes those tools.
   * Symfony Flex recipes: follow
     [update-symfony-recipes](../update-symfony-recipes/SKILL.md) for inspection
     with
     `composer recipes`, targeted updates, conflict handling, and validation.
     Do not assume a package upgrade also updates its installed recipe.
   * Docker images: follow
     [update-docker-images](../update-docker-images/SKILL.md) for image selection,
     multi-platform digest pinning, and validation.
   * GitHub Actions: follow [ACTIONS_UPDATE.md](../../../doc/ACTIONS_UPDATE.md)
     and use
     `make check-action-updates` or `bash scripts/check-action-updates.sh` as
     appropriate. Keep immutable SHA pins and update their version comments
     consistently.
3. Review the proposed version changes against the current constraints and
   supported PHP/Symfony versions in `composer.json`, package compatibility,
   security advisories, and relevant upstream changelogs. Do not widen
   constraints, cross a major version, or update unrelated packages unless the
   request requires it. Keep Symfony packages aligned with the configured
   Symfony version.
4. Review generated lockfile and recipe changes. Keep lockfiles in sync with
   their manifest, inspect changes to `symfony.lock` and recipe-generated
   configuration, and retain only changes required by the requested updates.
   Do not hand-edit lockfiles or replace dependency management with manual
   version pinning.
5. Include dependency scopes and versions changed, plus notable compatibility
   or recipe changes, in the handoff.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* For Composer changes, run `make composer-validate`, `make check-security`,
  and `make run-tests`.
* For Docker and GitHub Actions updates, run their documented pin/update
  checks and relevant CI checks.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
