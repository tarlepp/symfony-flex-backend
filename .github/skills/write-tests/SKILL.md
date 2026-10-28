---
name: write-tests
description: Add or update PHPUnit tests using this repository's E2E, functional, integration, and unit test conventions.
---

# What is this?

<a id="what-is-this"></a>

Use this skill whenever adding or changing tests. Select the test category that
matches the behavior boundary and dependencies under test.

Editing documentation about PHPUnit, AI instructions, or skill text does not
itself require running PHPUnit. Use
[the validation selection guide](../../../doc/TESTING.md#selecting-validation)
to decide whether tests, report producers, or non-PHP checks are relevant.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Test categories](#test-categories)
    * [Using stubs and mocks](#using-stubs-and-mocks)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Test categories [ᐞ](#table-of-contents)

<a id="test-categories"></a>

Choose the category by the behavior boundary and dependencies under test:
complete request-to-response behavior belongs in E2E; application behavior
using the real database but not requiring the full request flow belongs in
Functional; collaboration between selected services belongs in Integration;
and isolated logic belongs in Unit.

* **E2E** (`tests/E2E/`): exercise the application end to end, including the
  real database. Do not mock dependencies. Use when correctness depends on the
  complete request-to-response flow.
* **Functional** (`tests/Functional/`): exercise application functionality
  against the real database without mocks. Use for behavior at a functional
  application boundary that does not need a complete E2E request flow.
* **Integration** (`tests/Integration/`): test how the application's own
  services and framework components handle behavior and work together. Keep
  the components being integrated real and mock only unrelated collaborators.
  Usually avoid the database; use it when database interaction is itself part
  of the integration being tested, following the existing test setup.
* **Unit** (`tests/Unit/`): test a single unit in isolation, using no mocks or
  only the minimum needed. Do not use a database or application-wide behavior.

## Using stubs and mocks [ᐞ](#table-of-contents)

<a id="using-stubs-and-mocks"></a>

Use a **stub** when a dependency needs to return controlled values or throw a
controlled exception so the test can focus on the unit's observable result.
Create one with PHPUnit's `createStub()` and configure its responses with
`willReturn()` or `willThrowException()`. Do not add call expectations to a
stub.

Use a **mock** when the interaction with a dependency is itself part of the
behavior being tested, such as ensuring a notification is sent once or a
forbidden operation is never called. Create one with `createMock()` and assert
only meaningful expectations with `expects()` and, when relevant, `with()`.
Avoid expectations on incidental implementation details, such as private
control flow or calls that do not affect the contract.

Prefer real collaborators when their behavior is part of the test, and use the
fewest test doubles needed. For behavior that depends on the real database or
full request flow, follow the test-category guidance instead of replacing those
dependencies with doubles.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Inspect nearby tests in the matching category, including their test case,
   fixture setup, and database lifecycle. Follow existing helpers and
   conventions.
2. Test observable behavior and meaningful edge cases at the narrowest
   appropriate category. For API behavior, cover response status and shape,
   validation, authentication, and authorization as relevant.
3. For writes, assert the resulting state or response, not only that the
   operation did not throw. Keep data isolated and follow established fixture
   and data-provider patterns. When persistence is the contract, verify the
   stored result rather than only the mutated in-memory entity. Reload or
   refresh through the existing test setup when needed to detect missing
   flushes, mapping problems, or relationship ownership mistakes.
4. Avoid asserting incidental implementation details. When behavior changes,
   update assertions for the old contract without weakening unrelated security
   or regression coverage.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

Run targeted tests from the repository root inside the PHP development
container. Use `make bash` or `make fish` from the host to open that shell; an
IDE Dev Container terminal is already inside the container.

Use just `phpunit`; no explicit path is needed inside the container. The
development `Dockerfile` adds `/app/vendor/bin` to `PATH`, so this command uses
the project's Composer-installed runner, not a separate global installation.
Do not use `bin/phpunit`, which does not exist in this project, or run project
tests on the host.

For example, inside the container:

```bash
phpunit tests/E2E/Controller/v1/User/UserControllerTest.php
phpunit tests/Functional/Repository/UserRepositoryTest.php
phpunit tests/Integration/Resource/UserResourceTest.php
phpunit tests/Unit/Rest/SearchTermTest.php
```

Run the smallest relevant class or directory first, then `make run-tests` when
broader regression coverage is warranted. Use
[TESTING.md](../../../doc/TESTING.md) and the Makefile
for current runner details; do not add a new test framework or runner.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
