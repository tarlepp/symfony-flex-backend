# What is this?

This document contains basic information how you can run tests within this
application.

## Table of Contents

* [What is this?](#what-is-this)
   * [Table of Contents](#table-of-contents)
   * [Testing](#testing)
   * [Metrics](#metrics)

## Testing

Project contains bunch of tests _(E2E, Functional, Integration, Unit)_ which 
you can run simply by following command:

```bash
make run-tests
```

And if you want to run tests with [fastest](https://github.com/liuggio/fastest)
library use following command:

```bash
make run-tests-fastest
```

Note that you need to create `.env.test` file to define your testing
environment. This file has the same content as the main `.env` file, just
change database and others to match your testing environment.

* [PHPUnit](https://phpunit.de/)

Or you could easily configure your IDE to run these for you.

## Metrics

Project also contains [PhpMetrics](https://github.com/phpmetrics/phpmetrics)
to make some analyze of your code. Note that you need run tests before this
command. You can run this by following command:

```
make phpmetrics
```

And after that open `build/phpmetrics/index.html` with your favorite browser.
