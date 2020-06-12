# What is this?

This document contains basic information how you can run tests within this
application.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Testing](#testing)
    * [Commands to run tests](#commands-to-run-tests)
    * [Parallel testing](#parallel-testing)
    * [Own environment for testing](#own-environment-for-testing)
  * [Metrics](#metrics)
  * [PhpStorm](#phpstorm)

## Testing

Project contains bunch of tests _(E2E, Functional, Integration, Unit)_ tests
itself relies to PHPUnit library.

* [PHPUnit](https://phpunit.de/)

Note that this project does not use simple phpunit as does Symfony by default.
So don't try to run `./bin/phpunit` command, because that does not exist.

### Commands to run tests

You can run tests by simply by following command(s):

```bash
make run-tests                # Runs all tests via phpunit (Uses phpdbg if that
                              # is installed)
make run-tests-php            # Runs all tests via phpunit (pure PHP)
make run-tests-phpdbg         # Runs all tests via phpunit (phpdbg)
make run-tests-fastest        # Runs all test via fastest (Uses phpdbg if that
                              # is installed)
make run-tests-fastest-php    # Runs all test via fastest (pure PHP)
make run-tests-fastest-phpdbg # Runs all test via fastest (phpdbg)
```

All of those above commands will run whole test suite, so it might take some
time to run those all.

If you just want to run single test or all tests in specified directory you
could use following command:

```bash
# Just this single test class
./vendor/bin/phpunit ./tests/Integration/Controller/ProfileControllerTest.php

# All tests in this directory
./vendor/bin/phpunit ./tests/Integration/Controller/
```

### Parallel testing

Note that all those `make` commands that contains `fastest` are actually run
with eight (8) different process with [fastest](https://github.com/liuggio/fastest)
library.

### Own environment for testing

If you need to use your own environment for testing, eg. change database or
another stuff you need to create `.env.local.test` file to define your testing
environment - if needed. This file has the same content as the main `.env.test`
file, just change database and others to match your testing environment.

## Metrics

Project also contains [PhpMetrics](https://github.com/phpmetrics/phpmetrics)
to make some analyze of your code. Note that you need run tests before this
command. You can run this by following command:

```bash
make phpmetrics
```

And after that open `build/phpmetrics/index.html` with your favorite browser.

## PhpStorm

Also note that you can run tests directly from your IDE (PhpStorm) - if you're
using that you should read [PhpStorm](PHPSTORM.md) documentation.

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
