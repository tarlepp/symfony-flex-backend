# What is this?

<a id="what-is-this"></a>

Shared execution guidance for repository skills. Individual skills should link
here for common setup, validation, and handoff rules, and keep only their
task-specific workflow and checks.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Using repository instructions](#using-repository-instructions)
    * [Running and validating changes](#running-and-validating-changes)
    * [Handoff](#handoff)

## Using repository instructions [ᐞ](#table-of-contents)

<a id="using-repository-instructions"></a>

Before applying a skill, read the
[repository instructions](../copilot-instructions.md) and
[AI rules](../../doc/AI_RULES.md). Follow those repository-wide rules alongside
the
task-specific workflow in the skill.

## Running and validating changes [ᐞ](#table-of-contents)

<a id="running-and-validating-changes"></a>

* Run project commands from the repository root inside the running PHP
  development container or IDE Dev Container. From the host, use `make bash` or
  `make fish`; if containers are stopped, start them with `make start`.
* Use [write-tests](write-tests/SKILL.md) as the single source for test
  selection and test-writing practice. Other skills should state only
  task-specific behaviors that tests must cover.
* Run relevant quality checks for the changed code. For PHP changes, these
  include `make ecs`, `make phplint`, `make phpstan`, and `make psalm`. Run
  `make lint-markdown` when Markdown files change.
* Before a commit, follow the full validation suite in
  [repository instructions](../copilot-instructions.md).
* If a required check cannot run, identify the blocker in the handoff.

## Handoff [ᐞ](#table-of-contents)

<a id="handoff"></a>

State what changed and which relevant tests and checks ran or were blocked. For
database changes, also state whether a migration was generated, reviewed, or
applied to a non-production database. Do not commit unless explicitly asked.

---

[Back to previous](README.md)

[Back to main README.md](../../README.md)
