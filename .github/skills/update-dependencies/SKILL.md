---
name: update-dependencies
description: Update Composer, Composer tool, Docker image, or GitHub Actions dependencies safely using this repository's update and validation workflows.
---

# Update dependencies

Use this skill when updating or reviewing dependency versions, lockfiles, base
images, or pinned GitHub Actions. Do not use it to add unrelated dependencies;
follow the feature-specific skill and repository rules when a feature requires
a new package.

Follow the shared skill guidance in `.github/skills/shared-guidance.md`.

## Update order

For Composer dependency updates, update the tool dependencies under `tools/`
first. Validate and commit those changes separately before updating the main
application dependencies in the root `composer.json` and `composer.lock`.
Do not combine tool and application dependency updates in the same commit.
If commit authorization has not been given, ask for it and wait before
committing or starting the main dependency update. If no tool updates are
available, state that and proceed without an empty commit.

Standalone Symfony Flex recipe updates are configuration/scaffolding updates,
not Composer package upgrades; this tool-first ordering does not apply to them.
Follow `.github/skills/update-symfony-recipes/SKILL.md` without upgrading packages.

## Workflow

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
     `.github/skills/update-symfony-recipes/SKILL.md` for inspection with
     `composer recipes`, targeted updates, conflict handling, and validation.
     Do not assume a package upgrade also updates its installed recipe.
   * Docker images: follow
     `.github/skills/update-docker-images/SKILL.md` for image selection,
     multi-platform digest pinning, and validation.
   * GitHub Actions: follow `doc/ACTIONS_UPDATE.md` and use
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
5. Run the relevant validation for the changed scope. For Composer changes,
   run `make composer-validate`, `make check-security`, and `make run-tests`;
   run the applicable quality checks from `.github/copilot-instructions.md`
   when the update could affect application code or build tooling. For Docker
   and GitHub Actions updates, run their documented pin/update checks and
   relevant CI checks. Follow the shared guidance for execution environment
   and report any checks that could not run.
6. Summarize the dependency scopes and versions changed, notable compatibility
   or recipe changes, and validation results. Do not commit unless explicitly
   asked.
