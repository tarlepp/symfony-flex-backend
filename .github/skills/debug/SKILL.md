---
name: debug
description: Diagnose unexpected behavior, runtime errors, and failing tests in this Symfony backend using repository evidence and focused reproduction.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when diagnosing an application error, unexpected behavior, or a
failing test in this repository. It covers finding and verifying a root cause;
use the relevant feature skill when implementing a broader feature, and use
[write-tests](../write-tests/SKILL.md) when adding or changing tests.

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

1. Establish the exact symptom, expected result, reproduction steps, and
   affected boundary. Distinguish a request, console command, and test failure;
   note the environment and relevant recent changes without assuming a cause.
2. Reproduce the problem using the narrowest applicable existing test or
   command. Inspect the entry point, code path, configuration, and nearby tests
   that participate in that behavior. Follow the actual implementation rather
   than inferring behavior from documentation alone.
3. Form a specific root-cause hypothesis and gather evidence that can confirm
   or reject it. Change one diagnostic variable at a time. Use an IDE debugger
   when it will clarify control flow; see the repository's
   [Xdebug guide](../../../doc/XDEBUG.md) and
   [PhpStorm guide](../../../doc/PHPSTORM.md) for setup.
4. Separate application defects from test-fixture, database, container,
   environment, and local IDE configuration problems. Do not mask failures by
   weakening assertions, disabling security checks, swallowing exceptions, or
   introducing success-shaped fallbacks.
5. If fixing the defect is in scope, make the smallest change that addresses
   the verified cause and add or update a regression test at the affected
   boundary. Follow [write-tests](../write-tests/SKILL.md) for test selection
   and conventions. Avoid unrelated cleanup.
6. If the behavior cannot be reproduced or evidence is incomplete, report
   what was observed, what was checked, and what remains uncertain. Do not
   present a hypothesis as a confirmed root cause or claim a fix is verified
   without rerunning the reproducer.
7. Keep diagnostic output and examples free of credentials, tokens, private
   configuration, and other sensitive data. Do not make production changes or
   destructive state changes as part of diagnosis.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run the smallest existing reproducer first and rerun it after any fix. For
  PHPUnit, run the selected test from the repository root inside the PHP
  development container; follow the [testing guide](../../../doc/TESTING.md)
  and [write-tests](../write-tests/SKILL.md) for supported test selection and
  commands.
* Run relevant PHP quality checks for PHP changes and expand regression
  coverage when the change affects shared behavior, following the
  [validation selection
  guide](../../../doc/TESTING.md#selecting-validation).
* At handoff, distinguish confirmed evidence from remaining hypotheses and
  state each check's result or why it could not run. Documentation changes
  alone do not require running application tests.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
