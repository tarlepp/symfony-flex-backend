---
name: update-symfony-recipes
description: Inspect and update Symfony Flex recipes with Composer, preserving project customizations, reviewing staged changes and conflicts, and validating affected configuration.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when inspecting recipe status or updating already-installed
Symfony Flex recipes. Recipe updates change application scaffolding and
`symfony.lock`; they are separate from upgrading Composer package versions.
Do not perform dependency upgrades unless separately requested.

Follow the [shared skill guidance](../shared-guidance.md), including
`.github/copilot-instructions.md` and `doc/AI_RULES.md`.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Inspecting recipes](#inspecting-recipes)
    * [Updating recipes](#updating-recipes)
    * [Validation](#validation)

## Inspecting recipes [ᐞ](#table-of-contents)

<a id="inspecting-recipes"></a>

Run Composer commands from the repository root inside the PHP development
container. From the host, open a shell with `make bash` or use
`docker compose exec -T php composer ...`.

```bash
composer recipes --help
composer recipes:update --help
composer recipes
composer recipes --outdated
composer recipes vendor/package
```

`composer recipes` lists recipes and available updates; it does not apply them.
The package-specific command reports the installed recipe, latest recipe,
history links, managed files, and the suggested update command. Read those
details and inspect the relevant upstream changes before applying an update.
Use `composer.json` and `composer.lock` for package compatibility and
`symfony.lock` for installed recipe metadata. A recipe's version is not the
installed package version.

Check the installed command help rather than assuming flags exist. In
particular, do not invent a dry-run option for `recipes:update`.

## Updating recipes [ᐞ](#table-of-contents)

<a id="updating-recipes"></a>

1. Confirm the requested packages and scope. Listing outdated recipes does not
   authorize updating all of them. Prefer one explicitly named recipe at a
   time so its changes and failures can be reviewed independently.
2. Inspect `git status --short`, staged and unstaged changes, and untracked
   paths before running the updater. The installed Flex updater requires both
   tracked working-tree changes and the index to be clean, although its error
   message mentions only the index. Its check ignores untracked files; inspect
   those for collisions with recipe-managed paths yourself. Never discard,
   stash, stage, or commit unrelated user work to satisfy this prerequisite.
   If tracked changes block the update, explain and ask the developer how to
   proceed. Do not create a checkpoint commit without authorization.
3. Read the recipe's affected files and the current application customizations
   before applying it. Recipe-managed files can include `.env`, package
   configuration, service wiring, routes, entry points, test configuration,
   Docker files, and AI instruction files such as `AGENTS.md` and `CLAUDE.md`.
   Upstream template changes are proposed scaffolding, not permission to
   overwrite project architecture, security policy, or repository instructions.
4. Run `composer recipes:update vendor/package` inside the PHP development
   container. With no package argument the command selects a recipe
   interactively; do not use that as an unattended bulk update. The command
   applies a patch against the installed recipe baseline and automatically
   stages patched files and `symfony.lock`. It does not create a commit in
   this repository. `--no-changelog` only suppresses changelog generation;
   it is not a preview or safety option.
5. Read all command output, including warnings and changelog information.
   Inspect `git status --short`, `git diff --cached`, and `git diff`. The
   updater can return success while reporting merge conflicts; check
   `git diff --name-only --diff-filter=U` and review affected files for conflict
   markers. Resolve conflicts by integrating required upstream changes with
   the existing project behavior, not by accepting an entire side blindly.
   Stage only reviewed, in-scope conflict resolutions when needed. Do not
   commit unless explicitly requested.
6. Review skipped paths as well as applied patches. The updater cannot detect
   changes to files copied from a package and reports those separately.
   Updates to files already absent from the application can be skipped, with
   an offer to save their diff to a patch file. Review whether those changes
   belong in a project-specific replacement rather than blindly restoring a
   deliberately removed file. Review warnings about files shared by multiple
   recipes. Remove only temporary review artifacts created by this task once
   they are no longer needed.
7. If the installed baseline is missing, too old, or cannot be patched, Flex
   may suggest `composer recipes:install vendor/package --force -v`.
   Do not automatically take that fallback: `recipes:install` reinstalls
   scaffolding and `--force` can overwrite customized files. Inspect
   `composer recipes:install --help`, explain the affected files, and obtain
   explicit approval before a forced reinstall. Do not use `--reset`, delete
   `symfony.lock`, or hand-edit recipe references to manufacture an update.
8. Preserve `APPLICATION_CONFIG` JSON-backed settings, environment overrides,
   dependency-injection conventions, and security guards. Follow
   `.github/skills/update-application-configuration/SKILL.md` for configuration
   integration and other task-specific skills when generated code changes.
   Recipe application alone does not prove the upstream defaults suit this
   application. Inspect any manifest, script, or dependency-lock changes and
   explain necessary changes instead of broadening into a package upgrade.
9. Validate and finish reviewing one recipe before starting another. A prior
   update leaves tracked changes, so the next invocation may be blocked.
   Ask how to checkpoint completed work if required; do not auto-commit or
   stash it. The tool-first ordering in the dependency skill applies to
   Composer package upgrades, not standalone recipe updates.
10. Update relevant setup or behavior documentation when an adopted recipe
    change affects contributors or deployments. Report the packages and recipe
    references changed, preserved customizations, conflicts or skipped paths,
    validation results, and staged versus unstaged state. A metadata-only
    `symfony.lock` update can be legitimate even when no application files
    need changes; inspect it rather than assuming failure.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Rerun `composer recipes vendor/package` to confirm the resulting recipe
  status. This verifies recorded metadata, not correctness of merged files.
* Run `git diff --check` and `git diff --cached --check`, check for unmerged
  paths, and review both staged and unstaged changes. Do not treat a zero
  updater exit status as proof of a conflict-free update.
* Run the checks appropriate to the actual changes: `make lint-yaml` and
  `bin/console lint:container` for Symfony configuration, affected environment
  and caller-boundary tests for behavior, and relevant PHP quality checks from
  shared guidance for generated PHP. Use `make composer-validate` if Composer
  metadata changes and `make lint-markdown` for Markdown changes. Validate
  affected Docker configuration from the host without recreating services.
* Follow the full repository pre-commit suite before an authorized commit.
  Report blockers explicitly; do not disable safeguards or overwrite local
  secrets to make validation pass.

---

[Back to previous](../shared-guidance.md)

[Back to main README.md](../../../README.md)
