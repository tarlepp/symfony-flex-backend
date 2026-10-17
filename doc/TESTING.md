# What is this?

<a id="what-is-this"></a>

This document contains basic information how you can run tests within this
application.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Testing](#testing)
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

## PhpStorm [ᐞ](#table-of-contents)

<a id="phpstorm"></a>

Also note that you can run tests directly from your IDE (PhpStorm) - if you're
using that you should read [PhpStorm](PHPSTORM.md) documentation.

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
