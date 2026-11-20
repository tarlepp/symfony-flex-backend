---
name: maintain-repository-scripts
description: Add or change scripts under scripts/ with correct responsibility-based placement, group-local tests, and complete caller and path updates.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding or changing scripts under `scripts/`, including shell
and Python helpers, regression tests, test runners, and their callers. It covers
placement, path integrity, and focused behavior validation. Use
[maintain-development-tooling](../maintain-development-tooling/SKILL.md) for tooling
contracts, Make routing, checker behavior, and shell readability reviews. Use
[document-shell-scripts](../document-shell-scripts/SKILL.md) for `.sh` headers and
explanatory comments. For CI behavior or dependency pins, also use the relevant
workflow or dependency skill.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and [AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Script tree and ownership](#script-tree-and-ownership)
    * [Workflow](#workflow)
    * [Callers and path integrity](#callers-and-path-integrity)
    * [Tests and validation](#tests-and-validation)

## Script tree and ownership [ᐞ](#table-of-contents)

<a id="script-tree-and-ownership"></a>

Use the [scripts guide](../../../scripts/README.md#directory-layout) as the current
source for group responsibilities. Existing groups include `ci/`, `checks/`,
`docker/`, `make/`, `output/`, and `reports/`. Put a script in the group that
owns its primary responsibility; extend an existing group rather than creating a
new group or leaving an ordinary script at the `scripts/` root without a clear
reason.

Keep focused regression tests in the owning group's `tests/` directory. Reserve
`scripts/tests/` for cross-group runners and shared test orchestration. When a
test exercises several groups, decide from its main responsibility whether it is
a focused group test or a cross-group runner; do not relocate it solely to make
paths shorter.

Follow the existing implementation for interpreter, naming, executable mode, and
test style. A directory move changes `__file__` and `BASH_SOURCE`-relative
paths: update root and sibling discovery deliberately, including isolated test
fixtures. Update the scripts guide when group responsibilities or test ownership
changes.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Read the scripts guide, the owning script or nearest equivalent, its focused
   tests, and relevant callers before editing. Check both direct invocation and
   indirect routes through Make, CI, Docker, actions, or another script.
2. Place new scripts and tests according to the ownership rules above. Extend
   existing helpers and test mechanisms where appropriate; do not create
   parallel wrappers or a new testing framework for a localized change.
3. Preserve the script's observable contract: interpreter, sourced-versus-run
   behavior, working-directory assumptions, argument boundaries, environment,
   output streams and formats, exit statuses, side effects, and network access.
   Ask before changing a public command, destructive behavior, supported
   platform, or failure policy.
4. Update every affected caller and path consumer, including CI path filters,
   ShellCheck inputs, Make recipes, documentation, test-runner inventories, and
   fixture copies when applicable. Distinguish runtime callers from static
   references and tests that only inspect source.
5. Add or update focused tests at the owning behavior boundary. Cover relevant
   success and failure paths, missing prerequisites, and subprocess errors. Mock
   Docker, GitHub, registries, and other external commands using existing
   fixture patterns; unexpected external calls should fail.
6. Review the complete diff for stale paths, executable modes, accidental
   behavior changes, and generated artifacts. Keep unrelated work untouched.

## Callers and path integrity [ᐞ](#table-of-contents)

<a id="callers-and-path-integrity"></a>

Trace references from the implementation outward. Depending on the script,
inspect:

* `Makefile` and `scripts/make/` for Make dispatch, recipes, and shell
  integration.
* `.github/workflows/` and `.github/actions/` for job invocations, path filters,
  cache keys, and embedded shell consumers.
* `Dockerfile`, `docker/`, and `.devcontainer/` for entrypoints and sourced
  integrations.
* The owning group's `tests/`, `scripts/tests/` cross-group runners, and
  `scripts/README.md` for test discovery and documented commands.

For paths derived from a script's own location, verify the expected repository
root and sibling paths after the move. For path-sensitive fixtures, reproduce
the new directory layout rather than placing files at the old flat paths. Search
for old references after updates and inspect each match; avoid blind global
replacements that can alter unrelated names or behavior.

When script behavior is part of Make, workflow, or container execution, use
[maintain-development-tooling](../maintain-development-tooling/SKILL.md) and, as
applicable, [update-github-workflows](../update-github-workflows/SKILL.md) or [update-docker-environment](../update-docker-environment/SKILL.md).
For shell documentation only, use [document-shell-scripts](../document-shell-scripts/SKILL.md).

## Tests and validation [ᐞ](#tests-and-validation)

<a id="tests-and-validation"></a>

Select validation for changed behavior, not only changed file extensions. Follow
the [testing guide](../../../doc/TESTING.md#selecting-validation) and the validation
section of [maintain-development-tooling](../maintain-development-tooling/SKILL.md#validation)
for the exact affected targets and execution boundaries.

From the repository root on the Docker host with the PHP development service
running, documentation-only changes require:

```bash
make lint-markdown
make check-skills
```

For changed Bash scripts, check syntax for each exact changed path inside the
PHP development container. Run the focused suite for the changed group and
affected aggregate or CI entry point when relevant. Do not treat documentation
checks or an offline mock as proof that a live workflow or external service ran.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
