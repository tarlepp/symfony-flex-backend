---
name: document-python-scripts
description: Document Python script headers, function contracts, and non-obvious logic while preserving script behavior.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when documenting headers, function contracts, or non-obvious
logic in tracked Python files (`*.py`). It does not authorize changing executable
behavior. Use [maintain-development-tooling](../maintain-development-tooling/SKILL.md)
for behavior changes and [maintain-repository-scripts](../maintain-repository-scripts/SKILL.md)
for script placement, paths, tests, and callers.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and [AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Python documentation conventions](#python-documentation-conventions)
    * [Caller context](#caller-context)
    * [Workflow](#workflow)
    * [Function documentation and code comments](#function-documentation-and-code-comments)
    * [Validation](#validation)

## Python documentation conventions [ᐞ](#table-of-contents)

<a id="python-documentation-conventions"></a>

Inspect the module and its callers before writing its header:

* Inventory tracked Python files from the repository root with
  `git ls-files '*.py'`. This includes entrypoints, helpers, tests, and
  cross-group runners.
* Read the complete target and relevant Make, CI, Docker, or Python callers.
  Verify invocation, working directory, inputs, environment variables,
  prerequisites, outputs, exit behavior, side effects, and execution host
  against the implementation.
* Place the module docstring after a shebang and encoding declaration and before
  imports such as `__future__`. Preserve both declarations. Use a short purpose
  sentence for new docstrings, and preserve existing docstrings when they supply
  CLI help or another observable runtime output.
* Distinguish direct execution, imports, and tests loaded by a runner. Do not
  show a standalone command for an import-only module. Note import-time behavior
  or runner-supplied arguments when they affect use.

Use a triple-quoted module docstring for the purpose, followed by a comment-only
header for all detailed maintainer documentation. This keeps the same visible
layout across Python scripts while preserving any CLI-visible docstring. For
non-observable existing docstrings, move detailed sections into the comment
header and retain the purpose sentence in the docstring. Do not prefix the
purpose with `Purpose:`.

Separate the docstring and comment header with a blank line. Start the comment
block with `#`, separate sections with a blank comment line, and put each
section label on its own comment line. Indent its content by two spaces after
the comment marker. Use only supported sections, such as `Usage:`, `Inputs:`,
`Environment:`, `Requirements:`, `Details:`, `Effects:`, `Exit Status:`, or
`Security Note:`. Do not create empty sections or force every module into an
identical list of headings.

Keep invocations readable and copyable. Follow the [shared command guidance](../shared-guidance.md#updating-documentation):
show the existing Make target first, then the direct invocation. List meaningful
modes or options separately under `Usage:`. For example:

```python
#!/usr/bin/env python3
"""Validate skill metadata, index coverage, and repository Markdown links."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make check-skills
#   Direct invocation from the repository root inside the container:
#     python3 scripts/checks/check-skills.py
#
# Requirements:
#   Python 3.
#
# Security Note:
#   Reads repository Markdown and does not execute scripts.
```

Keep sections accurate, concise, and specific to the module. Document
non-obvious defaults, safety boundaries, and important limitations. Avoid
repeating implementation, describing hypothetical behavior, or copying a lengthy
manual when a focused reference is more useful.

Preserve CLI-visible docstring text exactly, even when it contains more than a
purpose sentence. Do not change the help implementation merely to normalize the
header.

## Caller context [ᐞ](#table-of-contents)

<a id="caller-context"></a>

When repository callers add useful context, include a `Callers:` section. Group
details under headings such as `Makefile:`, `GitHub Actions:`, `Docker:`, or
`Scripts:` and list actual targets, workflow files, or script paths. For example:

```python
"""Validate skill metadata and repository documentation links."""

#
# Callers:
#   Makefile:
#     - `check-skills` target invokes this checker inside Docker.
```

Trace direct invocations and Make-to-workflow calls from current files,
including `Makefile`, `.github/workflows/`, `.github/actions/`, `Dockerfile`,
and neighboring scripts. Identify relations such as “invokes” or “via Make
target” when not apparent. Include only repository-owned runtime or development
callers. Static-analysis lists, path filters, documentation, and
source-inspection tests are not runtime callers. Omit the section when no useful
caller exists. Keep lists concise; do not enumerate every consumer of a generic
helper.

For runner-loaded tests, verify how the runner loads the module and passes
arguments. For example, [the workflow test runner](../../../scripts/tests/run-workflow-tests.py)
loads the supplied test file as a module before running `unittest`.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Establish whether the request is documentation-only. If it also changes
   script behavior, follow [maintain-development-tooling](../maintain-development-tooling/SKILL.md)
   for the behavior contract, callers, tests, and tooling validation.
2. Read the module and callers. Document supported invocations, inputs, outputs,
   status, side effects, and execution context. Distinguish host from container
   execution and tests from runtime entrypoints.
3. Add or refine the purpose docstring and comment header using the conventions
   above. Include a section only when it clarifies a real contract. Preserve
   observable docstrings, the shebang, encoding declaration, imports, and
   executable statements.
4. Review function definitions and their bodies as well as the header. Add
   function documentation or inline comments where they clarify a contract,
   explain a non-obvious choice, or mark a meaningful phase boundary. Separate
   logical phases with blank lines when useful; keep related statements together
   and avoid mechanical spacing.
5. Review the diff for unchanged executable statements, import order, shebangs,
   encoding declarations, and file modes. Do not run scripts if documenting them
   could mutate files, contact services, or affect shared containers.

## Function documentation and code comments [ᐞ](#table-of-contents)

<a id="function-documentation-and-code-comments"></a>

* Prefer a short function docstring for a contract that the name and signature
  do not convey. Cover relevant assumptions, return or failure behavior, and
  side effects. Leave self-explanatory helpers alone.
* Use a comment above the definition instead if changing `__doc__` would alter
  an observable output or contract.
* Explain why a non-obvious operation or constraint exists, not what the next
  statement visibly does. For example, explain why a parser preserves blank
  lines for diagnostic offsets or why a check deliberately skips some inputs.
* Add comments at meaningful phase boundaries when intent or transitions are
  unclear. Use blank lines between setup, validation, execution, and reporting
  phases where useful; do not separate every statement.
* Keep comments next to the code they clarify. Update or remove comments made
  inaccurate by a documentation change.
* Describe actual behavior, including failure handling and side effects; do not
  promise safety, validation, cleanup, portability, or read-only behavior unless
  the implementation and its callers establish it.
* Avoid narrating statements or adding comments only to increase coverage. Do
  not turn a documentation-only change into a refactor.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

For docstring- or comment-only changes, inspect the diff to confirm executable
statements and file modes are unchanged. For executable changes, follow the
[Python syntax and regression guidance](../maintain-development-tooling/SKILL.md#validation).

Run the documentation checks from the repository root on the Docker host with
development services running:

```bash
make lint-markdown
make check-skills
```

`check-skills` verifies skill metadata, index coverage, and local Markdown links
and anchors; it does not enforce whether a module's documented contract matches
its implementation. Report exact checks and any remaining blockers.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
