---
name: document-shell-scripts
description: Add or revise file headers and explanatory comments in shell scripts using the repository's documentation style while preserving script behavior.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding or revising file-level documentation or code comments
in tracked shell scripts (`*.sh`). It defines the repository's header layout and
comment-writing conventions; it does not authorize changing executable behavior.
For script logic, Make targets, tooling contracts, or behavior tests, use
[maintain-development-tooling](../maintain-development-tooling/SKILL.md).

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and [AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Shell documentation conventions](#shell-documentation-conventions)
    * [Caller context](#caller-context)
    * [Workflow](#workflow)
    * [Code comments](#code-comments)
    * [Validation](#validation)

## Shell documentation conventions [ᐞ](#table-of-contents)

<a id="shell-documentation-conventions"></a>

Inspect the script and its callers before writing its header:

* Inventory tracked shell scripts from the repository root with
  `git ls-files '*.sh'`. The inventory includes root entrypoints, scripts under
  `docker/`, shared and sourceable integrations, utilities, and test fixtures.
* Read the complete target script and relevant Make, Docker, CI, or shell
  callers. Verify invocation, working directory, inputs, environment variables,
  prerequisites, outputs, exit behavior, side effects, and execution host
  against the implementation.
* Use the script's shebang and sourced-versus-executed behavior to describe how
  it is used. Do not imply that a sourceable helper is an executable command or
  that a host-side script runs in the PHP container.

Place the header immediately after the shebang. For a sourceable file without a
shebang, place it at the beginning of the file. Use a comment-only block with an
unlabeled opening sentence that explains the script's purpose. Do not prefix
that sentence with `Purpose:`.

Separate the opening sentence and each section with a blank comment line. Put
each section label by itself, including its colon; place its content on the
following comment lines, indented by two spaces. Use only sections supported by
the script, such as `Usage:`, `Inputs:`, `Environment:`, `Requirements:`,
`Details:`, `Effects:`, `Exit Status:`, or `Security Note:`. Do not create empty
sections or force every script into an identical list of headings.

Follow the [shared command guidance](../shared-guidance.md#updating-documentation):
show the existing Make target first, then the direct invocation. Keep
invocations readable and copyable. List meaningful modes or options separately
under `Usage:`. For example:

```bash
#!/usr/bin/env bash

#
# Check pinned GitHub Actions and workflow tools for available updates.
#
# Usage:
#   make check-action-updates
#   ./scripts/checks/check-action-updates.sh
#   ./scripts/checks/check-action-updates.sh --current-pins-md
#   ./scripts/checks/check-action-updates.sh --tools-only
#
# Requirements:
#   - git and timeout for bounded release lookups
#
# Security Note:
#   Reports available updates but never applies them.
#
```

Keep section names and content accurate, concise, and specific to the file.
Document non-obvious defaults, relevant safety boundaries, and important
limitations when they affect how maintainers should use the script. Do not
repeat the implementation, describe hypothetical behavior, or copy a lengthy
manual into a header when a focused reference is more useful.

## Caller context [ᐞ](#table-of-contents)

<a id="caller-context"></a>

When repository-owned callers help explain where a script fits, add a `Callers:`
section. Group exact caller details under applicable headings such as `Makefile:`,
`GitHub Actions:`, `Docker:`, or `Scripts:`. The category headings provide the
high-level integration summary; list the actual target names, workflow files,
image entrypoints, or script paths beneath them. For example:

```bash
#
# Summarize analyzer output in a GitHub Actions job.
#
# Callers:
#   Makefile:
#     - `lint-workflows` target.
#   GitHub Actions:
#     - `.github/workflows/main.yml` (`lint-workflows` job, via Make).
#
```

Trace direct invocations, sourcing, and relevant Make-to-workflow call chains
from current files such as `Makefile`, `.github/workflows/`, `.github/actions/`,
`Dockerfile`, and neighboring scripts. Identify the relation when it is not
apparent, such as “sources,” “invokes,” or “via Make target.” Include only real
repository-owned runtime or development callers. Do not treat static-analysis
file lists, path filters, mentions in documentation, or tests that only inspect
source text as runtime callers. If a script has no useful repository caller to
document, omit this optional section rather than guessing or listing the
script's author as its caller.

Keep the list concise and maintainable: group related targets under their shared
Make macro or dispatcher when appropriate, and avoid enumerating every
transitive consumer of a generic helper. Do not add a caller list to general
documentation as a substitute for documenting the script's supported invocation
in `Usage:`.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Establish whether the request is documentation-only. If it also changes
   script behavior, follow [maintain-development-tooling](../maintain-development-tooling/SKILL.md)
   for the behavior contract, callers, tests, and tooling validation.
2. Read the script and relevant callers. Record only facts necessary to document
   its supported invocation, execution context, inputs, outputs, status, and
   side effects. Preserve distinctions such as interactive versus noninteractive
   use and host versus container execution.
3. Add or refine the header using the conventions above. Include a section only
   when it clarifies a real contract. For usage examples, preserve argument
   boundaries and show meaningful alternatives as separate commands.
4. Add a `Callers:` section when the repository integration is useful context.
   Verify every target and path against its actual invocation or source
   relationship; distinguish direct callers from callers that route through Make
   or another script.
5. Add code comments only where they explain non-obvious intent, shell
   semantics, a safety constraint, or a meaningful phase boundary. Prefer a
   focused comment at the relevant code over expanding the file header.
6. Review the diff to confirm that executable lines, quoting, ordering,
   shebangs, executable modes, and sourced-versus-executed behavior did not
   change. Do not run scripts whose documented behavior could mutate files,
   contact external services, or affect shared containers merely to validate
   their documentation.

## Code comments [ᐞ](#table-of-contents)

<a id="code-comments"></a>

* Explain why a non-obvious operation or constraint exists, not what the next
  command visibly does.
* Keep comments next to the code they clarify and update or remove comments made
  inaccurate by a documentation change.
* Describe actual behavior, including failure handling and side effects. Do not
  promise safety, validation, cleanup, portability, or read-only behavior unless
  the implementation and its callers establish it.
* Avoid narrating every command, adding comments solely to increase coverage, or
  turning a documentation-only change into a shell refactor.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

For documentation-only changes to shell comments, inspect the full diff and
confirm executable code and file modes are unchanged. Run Markdown checks when
this skill or other Markdown files change. Shell syntax and behavior checks are
needed when executable shell code changes, not merely because the changed file
has a `.sh` extension. For shell behavior changes, follow the validation
selection in [maintain-development-tooling](../maintain-development-tooling/SKILL.md#validation).

Run the documentation checks from the repository root on the Docker host with
development services running:

```bash
make lint-markdown
make check-skills
```

`check-skills` verifies skill metadata, index coverage, and local Markdown links
and anchors; it does not enforce whether a script's documented contract matches
its implementation. Report exact checks and any remaining blockers.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
