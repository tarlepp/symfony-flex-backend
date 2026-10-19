---
name: add-console-command
description: Add or change a Symfony console command using this repository's command, dependency-injection, input/output, and testing conventions.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding a command under `src/Command/` or changing a
command's arguments, options, execution, or output.

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

1. Check `composer.json` and `symfony.lock` for the installed PHP and Symfony
   Console versions and recipes. Inspect the closest command under
   `src/Command/`, its helpers or traits, and any related tests before choosing
   a pattern. Commands here use `#[AsCommand]`; some define a `NAME` constant
   and configure inputs in the constructor or `configure()`. Follow the
   relevant local pattern rather than imposing one style on every command.
2. Confirm the command's name, purpose, required inputs, options, interactive
   behavior, and expected exit statuses. Ask when missing details would change
   command behavior or affect destructive operations. Preserve existing CLI
   names and input/output contracts unless the request explicitly changes them.
3. Keep the command's own logic to the minimum needed for the CLI boundary:
   read and validate command input, call the appropriate application logic,
   format output, and return an appropriate status. First look for an existing
   service or resource that owns the operation and reuse it. If no suitable
   service exists, create one in the appropriate application layer and put the
   operation's business logic there; do not implement or duplicate that logic
   in the command. Keep reusable work out of command helpers and traits too.
4. Register commands using the repository's Symfony attribute and
   autowiring patterns. Use typed constructor dependencies and call the parent
   constructor as established by nearby commands. Define arguments and options
   with Symfony Console APIs or existing helpers such as `HelperConfigure`
   where they fit. Keep input names, defaults, descriptions, and requiredness
   clear.
5. Handle both interactive and non-interactive invocation deliberately.
   Do not assume prompts can run in scripts or CI; require needed input or
   provide a documented non-interactive path. Validate user-supplied values
   before performing operations. Report failures explicitly with useful
   console output and an appropriate non-success status; do not hide errors or
   report success after a failed operation.
6. Treat credentials and other sensitive values as secrets. Do not write them
   to logs or incidental output. If a command intentionally displays a newly
   created credential, follow the established credential-management behavior
   and avoid exposing it more than required.
7. If the command invokes another process, follow existing `Process` usage and
   pass arguments as an array rather than constructing a shell command string.
   Check failure status and surface actionable error information without
   leaking secrets.
8. Follow [write-tests](../write-tests/SKILL.md) for test selection and
   conventions. Use Symfony Console test tools such as `CommandTester` when
   testing command input, output, exit status, and interactive/non-interactive
   behavior. Cover success and relevant invalid-input or failure paths; test
   business logic at its service/resource boundary rather than duplicating it
   in command tests. Add focused service tests when creating a service.
9. Update command documentation, help text, or related usage docs when the
   command's public CLI contract or expected operations change.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run the relevant command tests and PHP quality checks following the shared
  skill guidance.
* Inspect the generated command help or invoke the command with safe test
  inputs when that verifies its CLI contract. Do not run destructive commands
  against production or alter persistent data without explicit approval.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
