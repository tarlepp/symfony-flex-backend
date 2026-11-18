---
name: maintain-repository-scripts
description: Add, move, or reorganize scripts under scripts/ with responsibility-based placement, colocated regression tests, and complete caller and path updates.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding, moving, or reorganizing scripts under `scripts/`,
or when changing their directory ownership and focused test placement. It
covers the repository's script layout and the path/caller updates required to
keep moves complete. Use
[maintain-development-tooling](../maintain-development-tooling/SKILL.md) for
command behavior, Make routing, checker contracts, and tooling regressions. Use
[document-shell-scripts](../document-shell-scripts/SKILL.md) for `.sh` headers
and explanatory comments.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Layout and ownership](#layout-and-ownership)
    * [Move and add workflow](#move-and-add-workflow)
    * [Regression tests](#regression-tests)
    * [Validation](#validation)

## Layout and ownership [ᐞ](#table-of-contents)

<a id="layout-and-ownership"></a>

Use [the scripts guide](../../../scripts/README.md) as the canonical map:

* Place each script in the existing group that owns its responsibility. Extend
  an existing group rather than creating a parallel directory or framework.
* Keep focused tests in that group's `tests/` directory. Reserve
  `scripts/tests/` for runners and suites that span multiple groups.
* Keep shared helpers with their owning responsibility. If several groups rely
  on a helper, place it in the existing shared group rather than copying it.
* Update the scripts guide when directory ownership, test placement, or
  documented invocation paths change.

## Move and add workflow [ᐞ](#table-of-contents)

<a id="move-and-add-workflow"></a>

1. Inspect the scripts guide, neighboring scripts and tests, and existing
   callers before choosing a location. Trace references from Make targets,
   GitHub workflows/actions, Docker configuration, and other scripts.
2. When moving or renaming a script or helper, search for both literal paths
   and path-derived lookups. Inspect `__file__`/`dirname` parent calculations,
   sibling assumptions, and fixture setup that copies or invokes helpers;
   update those references to match the production directory tree.
3. Preserve command behavior and execution boundaries, including shebang,
   executable mode, working-directory assumptions, arguments, environment,
   output, exit status, and container/host execution. For behavior changes,
   follow [maintain-development-tooling](../maintain-development-tooling/SKILL.md).
4. Update every affected caller and its regression fixture. Keep test fixtures
   aligned with real script/helper relationships rather than making moved files
   appear to be siblings. Update documentation and test-runner inventories when
   the supported paths or commands change.

## Regression tests [ᐞ](#table-of-contents)

<a id="regression-tests"></a>

Add or update a focused test in the owning group's `tests/` directory when
script behavior or path resolution changes. Exercise the moved script/helper
through its actual path and verify relevant success and failure behavior.
Include the owning suite in the relevant runner or CI command where applicable;
run both the focused test and that aggregate entry point when the move affects
its fixture inventory. For checks and Make/CI behavior details, use
[maintain-development-tooling](../maintain-development-tooling/SKILL.md).

## Validation [ᐞ](#validation)

<a id="validation"></a>

Run relevant commands from the repository root on the Docker host; project
tooling runs through the Compose PHP development container using existing Make
targets. For documentation or skill changes, run:

```bash
make lint-markdown
make check-skills
```

For changed Bash scripts, run `bash -n` on each changed path inside the PHP
development container. Run the focused regression suite and affected aggregate
or CI entry point for script moves; do not treat documentation checks or a
mocked fixture as proof that a live workflow or external service ran.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
