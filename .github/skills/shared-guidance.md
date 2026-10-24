# What is this?

<a id="what-is-this"></a>

Shared workflow guidance for repository skills. Individual skills should link
here for common preparation, validation, and handoff rules, and keep only their
task-specific workflow and checks.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Using repository instructions](#using-repository-instructions)
    * [Preparing a change](#preparing-a-change)
    * [Defining the contract](#defining-the-contract)
    * [Running and validating changes](#running-and-validating-changes)
    * [Updating documentation](#updating-documentation)
    * [Handoff](#handoff)

## Using repository instructions [ᐞ](#table-of-contents)

<a id="using-repository-instructions"></a>

Before applying a skill, read the
[repository instructions](../copilot-instructions.md) and
[AI rules](../../doc/AI_RULES.md), following the canonical inventory and
reading order in the [README](../../README.md#ai-assisted-development). Follow
those repository-wide rules alongside the task-specific workflow in the skill.

## Preparing a change [ᐞ](#table-of-contents)

<a id="preparing-a-change"></a>

* Inspect staged, unstaged, and untracked changes before editing. Preserve
  unrelated work; ask if it conflicts with the task. Tool-specific clean-tree
  requirements and generated-file handling belong in the relevant skill.
* Before framework-dependent work, check `composer.json` for supported PHP
  and package constraints, `composer.lock` for locked versions, and
  `symfony.lock` for Flex recipe metadata. Do not treat recipe versions as
  installed package versions or assume an uninstalled component is available.
  If a command's behavior depends on the installed tooling, inspect its help
  or installed implementation; the vendor directory can differ from the lock.
* Read the affected implementation, configuration, callers, and nearby tests.
  Trace the actual behavior boundary before editing; use existing code and
  supported package contracts over remembered defaults or outdated docs.
  Individual skills identify the concrete files and boundaries to inspect.
* Reuse existing helpers and extend the appropriate implementation rather than
  adding a parallel pattern. Keep changes within the requested scope.

## Defining the contract [ᐞ](#table-of-contents)

<a id="defining-the-contract"></a>

Establish the inputs, outputs, defaults, failure behavior, and compatibility
requirements affected by the change. Ask before resolving unspecified choices
that alter API, CLI, schema, security, or deployment behavior. Preserve existing
contracts and security guards unless the requested change explicitly requires
an approved adjustment. Each skill lists its domain-specific decisions and
safeguards; do not replace them with generic assumptions.

## Running and validating changes [ᐞ](#table-of-contents)

<a id="running-and-validating-changes"></a>

* Run project commands from the repository root inside the running PHP
  development container or IDE Dev Container. From the host, use `make bash` or
  `make fish`; if containers are stopped, start them with `make start`.
  Non-interactive commands can use `docker compose exec -T php <command>`.
  Docker inspection, Compose validation, and image builds run from the host,
  as described by the relevant skill.
* Add or update tests for behavior changes. Use
  [write-tests](write-tests/SKILL.md) as the single source for test
  selection, fixtures, test doubles, runner commands, and test-writing
  practice whenever adding or changing tests. Other skills should state only
  task-specific behaviors, references, and boundaries that tests must cover.
* Run the smallest relevant tests first. For wiring or registration changes,
  verify behavior through the real caller or configured component: direct
  method calls alone do not prove that wiring works. Preserve domain-specific
  assertions and negative cases specified by the skill.
* For Symfony service wiring or package configuration changes, run
  `bin/console lint:container` and check the affected environments. Container
  linting is not a substitute for caller-boundary tests or runtime env
  resolution. Use `make lint-yaml` when configuration YAML changes.
* Run relevant quality checks for the changed code. For PHP changes, these
  include `make ecs`, `make phplint`, `make phpstan`, and `make psalm`. Run
  `make lint-markdown` when Markdown files change.
  While iterating on documentation-only changes, Markdown lint and checks of
  affected links and structure are sufficient; do not run application tests
  merely because a skill documents test commands.
* Before a commit, follow the full validation suite in
  [repository instructions](../copilot-instructions.md).
* If a required check cannot run, identify the blocker in the handoff.

## Updating documentation [ᐞ](#table-of-contents)

<a id="updating-documentation"></a>

Update existing documentation when behavior, architecture, configuration, or
contributor/deployment commands change. Keep public API metadata and CLI help
aligned with the implemented contract where affected. Follow the repository
Markdown format and use relative links to existing Markdown files. Individual
skills identify domain-specific documents or metadata; avoid creating parallel
guidance or duplicating fast-changing versions from manifests.

## Handoff [ᐞ](#table-of-contents)

<a id="handoff"></a>

State what changed, which files were affected, and each validation command with
its result or reason it was skipped or blocked. Distinguish checks of skill
documentation from execution of the workflow it describes; writing a recipe
skill, for example, does not imply that recipes were updated.

For database changes, state whether a migration was generated, reviewed, or
applied to a non-production database. Include proposed commit messages per
logical change using `Type(scope): short description`. Do not commit unless
explicitly asked.

---

[Back to previous](README.md)

[Back to main README.md](../../README.md)
