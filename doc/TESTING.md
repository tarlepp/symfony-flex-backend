# What is this?

<a id="what-is-this"></a>

This document contains basic information how you can run tests within this
application.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Testing](#testing)
      * [Selecting validation](#selecting-validation)
      * [Commands to run tests](#commands-to-run-tests)
      * [Own environment for testing](#own-environment-for-testing)
    * [Metrics](#metrics)
    * [PhpStorm](#phpstorm)

## Testing [ᐞ](#table-of-contents)

<a id="testing"></a>

Project contains bunch of tests _(E2E, Functional, Integration, Unit)_ tests
itself relies to PHPUnit library.

* [PHPUnit](https://phpunit.de/)

Run the project's Composer-installed PHPUnit runner with `phpunit` inside the
PHP development container.

Do not use Symfony's `bin/phpunit` entry point, which does not exist in this
project.

### Selecting validation [ᐞ](#table-of-contents)

<a id="selecting-validation"></a>

Choose checks by the behavior affected, including callers and generated
outputs, rather than running PHPUnit for every AI task. These rules apply
during iteration, at handoff, and before commit. They govern local validation;
they do not disable or change required GitHub CI checks.

* **Documentation only:** for Markdown, AI instructions, skill text, comments,
  or non-executable examples, run `make lint-markdown` and
  `python3 scripts/check-skills.py` when Markdown changes. Review example
  commands, links, and claims against the implementation. Do not run PHPUnit,
  PHP static analysis, report producers, or commands described by a skill just
  because their documentation changed. For comments in code, use relevant
  syntax/style checks; annotations or metadata that tools execute are not
  comments-only changes.
* **Application behavior:** for PHP logic, API contracts, validation,
  authentication, serialization, or persistence changes, add or update tests
  at the affected boundary and run the smallest relevant class or directory.
  Run relevant PHP quality checks and expand to the broader application suite
  in [AGENTS.md](../AGENTS.md#testing-and-validation) for cross-cutting changes,
  shared infrastructure, or uncertain regression impact.
* **Tests only:** run changed tests and affected consumers of shared fixtures,
  test helpers, or bootstrap code, plus relevant PHP quality checks. Changes
  to PHPUnit configuration, suite discovery, coverage configuration, or test
  runners require exercising those contracts, not only one passing test.
* **Workflows and artifacts:** use workflow-aware, composite-action, and shell
  validation as applicable. Trigger, permission, runner, action-pin, or
  documentation changes alone do not require local PHPUnit. Changes to test
  execution, coverage, report generation, or report paths require exercising
  the affected producer and checking its actual outputs. A targeted test
  cannot prove full-suite discovery or complete coverage artifact generation.
  Preserve failed-run diagnostics and distinguish local checks from hosted
  workflow execution.
* **Configuration and database:** run the relevant configuration/container
  lint and tests that exercise affected wiring or environment resolution.
  Entity or migration changes also require schema/migration validation and
  relevant database tests. Executable configuration is not documentation-only,
  even when no PHP source changes.
* **Dependencies, runtime, and containers:** follow the matching dependency,
  PHP-runtime, or Docker skill. Application package or runtime changes need
  affected application tests and broader coverage where compatibility is
  cross-cutting. Tool-only dependencies or image-pin helper changes need the
  affected tool checks; do not run PHPUnit unless application or test-runtime
  behavior is affected. Validate Compose and image builds when relevant.
* **Scripts and other tooling:** run the affected tool's syntax, lint, and
  regression checks. A documentation checker or shell helper change does not
  require PHPUnit unless it changes application/test execution. Skill-bundled
  executable scripts follow this rule, not the skill-text exemption.

For mixed changes, combine the relevant checks for the entire changeset;
documentation edits do not exempt accompanying behavioral changes. Inspect
the diff before deciding that a change is non-behavioral. When impact is
unclear, trace callers and expand validation rather than assuming no tests
are needed. Rerun affected checks after the final edit; earlier passing
results only cover the content tested then.

At handoff, state which checks ran, their results, and why PHPUnit was not
applicable or could not run. Missing containers, dependencies, credentials,
or services are blockers, not exemptions: do not claim blocked checks passed.
Use the development container for PHP tooling and the documented command for
each other check; do not replace a required runtime check with a YAML parse.

### Commands to run tests [ᐞ](#table-of-contents)

<a id="commands-to-run-tests"></a>

You can run tests by simply by following command(s):

```bash
make run-tests                # Runs all tests via phpunit (Uses phpdbg if that
                              # is installed)
make run-tests-php            # Runs all tests via phpunit (pure PHP)
make run-tests-phpdbg         # Runs all tests via phpunit (phpdbg)
```

All of those above commands will run whole test suite, so it might take some
time to run those all.

If you just want to run a single test or all tests in a specified directory,
run `phpunit` directly from a shell inside the PHP development container, not
on the host machine:

From the repository root on the host, open the container shell with:

```bash
make bash
# Or, for a Fish shell:
make fish
```

If the containers are stopped, run `make start` on the host first, then open the
container shell from a second host terminal. An IDE Dev Container terminal is
already inside the container.

Then run the tests from the repository root inside the container:

```bash
# Just this single test class
phpunit tests/Integration/Controller/v1/User/UserControllerTest.php

# All tests in this directory
phpunit tests/Integration/Controller/
```

Use just `phpunit` inside the container; no explicit path is needed. The
development `Dockerfile` adds `/app/vendor/bin` to `PATH`, so this command
resolves to the project's Composer-installed runner, not a separate global
installation.

### Own environment for testing [ᐞ](#table-of-contents)

<a id="own-environment-for-testing"></a>

If you need to use your own environment for testing, eg. change database or
another stuff you need to create `.env.local.test` file to define your testing
environment - if needed. This file has the same content as the main `.env.test`
file, just change database and others to match your testing environment.

## Metrics [ᐞ](#table-of-contents)

<a id="metrics"></a>

Project also contains [PhpMetrics](https://github.com/phpmetrics/phpmetrics)
to make some analyze of your code. Note that you need run tests before this
command. You can run this by following command:

```bash
make phpmetrics
```

And after that open `build/phpmetrics/index.html` with your favorite browser.

CI requires nonempty Clover (`build/logs/clover.xml`), JUnit
(`build/logs/junit.xml`), HTML coverage (`build/report/index.html`), and
PhpMetrics (`build/phpmetrics/index.html`) reports. It retains these artifacts,
plus Psalm and PHPLoc reports, for 14 days under their existing artifact names.
After a test failure, CI still uploads available reports without changing the
test result. PhpMetrics runs only when JUnit is present, nonempty, and contains
test suites, preventing its Make target from rerunning tests for missing input.

## PhpStorm [ᐞ](#table-of-contents)

<a id="phpstorm"></a>

Also note that you can run tests directly from your IDE (PhpStorm) - if you're
using that you should read [PhpStorm](PHPSTORM.md) documentation.

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
