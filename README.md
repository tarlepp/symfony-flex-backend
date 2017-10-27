# What is this

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![Build Status](https://travis-ci.org/tarlepp/symfony-flex-backend.png?branch=master)](https://travis-ci.org/tarlepp/symfony-flex-backend)
[![Coverage Status](https://coveralls.io/repos/github/tarlepp/symfony-flex-backend/badge.svg?branch=master)](https://coveralls.io/github/tarlepp/symfony-flex-backend?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tarlepp/symfony-flex-backend/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tarlepp/symfony-flex-backend/?branch=master)
[![Maintainability](https://api.codeclimate.com/v1/badges/69d6dc6b9fb4791e6b92/maintainability)](https://codeclimate.com/github/tarlepp/symfony-flex-backend/maintainability)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e59c1ed3-b870-457a-971e-570a27a04784/mini.png)](https://insight.sensiolabs.com/projects/e59c1ed3-b870-457a-971e-570a27a04784)

JSON REST API which is build on top of [Symfony](https://symfony.com/) framework.

Note that this project is built with
[Symfony Flex](https://github.com/symfony/flex),
although this project is using latest stable packages, but note that we're going
to update Symfony itself to `4.x.x` as soon as possible.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Requirements](#requirements)
  * [Installation](#installation)
    * [1. Clone repository](#1-clone-repository)
    * [2. Configuration](#2-configuration)
    * [3. Dependencies installation](#3-dependencies-installation)
    * [4. Create JWT auth keys](#4-create-jwt-auth-keys)
    * [5. File permissions](#5-file-permissions)
    * [6. Environment checks](#6-environment-checks)
      * [CLI environment](#cli-environment)
      * [Web-server environment](#web-server-environment)
        * [Apache](#apache)
    * [7. Other (optionally)](#7-other-optionally)
      * [Allow other IP's to access dev environment](#allow-other-ips-to-access-dev-environment)
  * [Commands](#commands)
    * [Makefile](#makefile)
    * [Symfony console](#symfony-console)
    * [Custom commands](#custom-commands)
      * [user:management](#usermanagement)
      * [api-key:management](#api-keymanagement)
  * [Structure](#structure)
  * [Development](#development)
    * [IDE](#ide)
    * [PHP Code Sniffer](#php-code-sniffer)
    * [Database changes](#database-changes)
  * [Testing](#testing)
  * [Metrics](#metrics)
  * [Links / resources](#links--resources)
  * [Authors](#authors)
  * [License](#license)

## Requirements

* PHP 7.1.3 or higher
* [Composer](https://getcomposer.org/)
* Database that is supported by [Doctrine](http://www.doctrine-project.org/)

## Installation

### 1. Clone repository

Use your favorite IDE and get checkout from GitHub or just use following command

```bash
git clone https://github.com/tarlepp/symfony-flex-backend.git
```

### 2. Configuration

Next you need to create `.env` file, which contains all the necessary
environment variables that application needs. You can create it by following
command (in folder where you cloned this project):

```bash
cp .env.dist .env
```

Then open that file and make necessary changes to it. Note that this `.env`
file is ignored on VCS.

### 3. Dependencies installation

Next phase is to install all needed dependencies. This you can do with following
command, in your project folder:

```bash
composer install
```

Or if you haven't installed composer globally

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

### 4. Create JWT auth keys

Application uses JWT to authenticate users, so we need to create public and
private keys to sign those. You can create new keys with following command.

```bash
make generate-jwt-keys
```

### 5. File permissions

Next thing is to make sure that application `var` directory has correct
permissions. Instructions for that you can find
[here](https://symfony.com/doc/current/setup/file_permissions.html).

_I really recommend_ that you use `ACL` option in your development environment.

### 6. Environment checks

To check that your environment is ready for this application. You need to make
two checks; one for CLI environment and another for your web-server environment.

#### CLI environment

You need to run following command to make all necessary checks.

```bash
./vendor/bin/requirements-checker
```

#### Web-server environment

Open terminal and go to project root directory and run following command to
start standalone server.

```bash
./bin/console server:start
```

Open your favorite browser with `http://127.0.0.1:8000/check.php` url and
check it for any errors.

##### Apache

To get JWT authorization headers to work correctly you need to make sure that
your Apache config has mod_rewrite enabled. This you can do with following
command:

```bash
sudo a2enmod rewrite
```

### 7. Other (optionally)

#### Allow other IP's to access `dev` environment

If you want to allow another IP addresses or _all_ to your `dev` environment
see `/allowed_addresses.php` file for detailed information how you can allow
certain IP addresses to have access to your `dev` environment.

## Commands

### Makefile

Symfony Flex comes with `Makefile` configuration so that you can easily run
some generic commands via `make` command. Below is a list of currently
supported (main commands) make commands, note that you can get this same list
with just running `make` command:

```bash
cache-clear       Clears the cache
cache-warmup      Warms up an empty cache
generate-jwt-keys Generates JWT auth keys
phpmetrics        Generates PhpMetrics static analysis
run-tests-fastest Runs all test via fastest
run-tests         Runs all tests via phpunit
serve             Runs a local web server
```

### Symfony console

You can list all Symfony console commands via following command:

```bash
./bin/console
```

### Custom commands

Project contains following custom console commands to help eg. user management:

```bash
./bin/console user:management     # To manage your users and user groups
./bin/console api-key:management  # To manage your API keys
```

#### user:management

This command is just a wrapper for following commands:

```bash
./bin/console user:create         # To create user
./bin/console user:create-group   # To create user group
./bin/console user:create-roles   # To initialize user group roles
./bin/console user:edit           # To edit user
./bin/console user:edit-group     # To edit user group
./bin/console user:remove         # To remove user
./bin/console user:remove-group   # To remove user group
./bin/console user:list           # To list current users
./bin/console user:list-groups    # To list current user groups
```

#### api-key:management

This command is just a wrapper for following commands:

```bash
./bin/console api-key:create          # To create API key
./bin/console api-key:edit            # To edit API key
./bin/console api-key:change-token    # To change API key token
./bin/console api-key:remove          # To remove API key
./bin/console api-key:list            # To list API keys
```

## Structure

todo

## Development

* [Coding Standards](http://symfony.com/doc/current/contributing/code/standards.html)

### IDE

I highly recommend that you use "proper"
[IDE](https://en.wikipedia.org/wiki/Integrated_development_environment)
to development your application. Below is short list of some popular IDEs that
you could use.

* [PhpStorm](https://www.jetbrains.com/phpstorm/)
* [NetBeans](https://netbeans.org/)
* [Sublime Text](https://www.sublimetext.com/)
* [Visual Studio Code](https://code.visualstudio.com/)

Personally I recommend PhpStorm, but just choose one which is the best for you.

### PHP Code Sniffer

It's highly recommended that you use this tool while doing actual development
to application. PHP Code Sniffer is added to project ```dev``` dependencies, so
all you need to do is just configure it to your favorite IDE. So the `phpcs`
command is available via following example command.

```bash
./vendor/bin/phpcs -i
```

If you're using [PhpStorm](https://www.jetbrains.com/phpstorm/) following links
will help you to get things rolling.

* [Using PHP Code Sniffer Tool](https://www.jetbrains.com/help/phpstorm/10.0/using-php-code-sniffer-tool.html)
* [PHP Code Sniffer in PhpStorm](https://confluence.jetbrains.com/display/PhpStorm/PHP+Code+Sniffer+in+PhpStorm)

### Database changes

When you start a new project where you use this project as a "seed" first thing
to do is to run following command:

```bash
./bin/console doctrine:migrations:diff
```

This will create a migration file which contains all necessary database changes
to get application running with default database structure. You can migrate
these changes to your database with following command:

```bash
./bin/console doctrine:migrations:migrate
```

After that you can start to modify or delete existing entities or create your
own ones. Easiest way to make this all work is to follow below workflow:

1. Make your changes (create, delete, modify) to entities in `/src/Entity/` folder
1. Run `diff` command to create new migration file
1. Run `migrate` command to make actual changes to your database
1. Run `validate` command to validate your mappings and actual database structure

Those commands you can run with `./bin/console doctrine:migrations:<command>`.

With this workflow you get easy approach to generic database changes on your
application. And you don't need to make any migrations files by hand (just let
Doctrine handle those). Although remember to really take a closer look of those
generated migration files to make sure that those doesn't contain anything that
you really don't want.

## Testing

Project contains bunch of tests _(Functional, Integration, Unit)_ which you can
run simply by following command:

```bash
make run-tests
# or alternative
./vendor/bin/phpunit
```

And if you want to run tests with [fastest](https://github.com/liuggio/fastest)
library use following command:

```bash
make run-tests-fastest
# or alternative
./vendor/bin/fastest -x phpunit.xml.dist
# or another alternative
find tests/ -name "*Test.php" | ./vendor/bin/fastest -v
```

Note that you need to create `.env.test` file to define your testing
environment. This file has the same content as the main `.env` file, just
change database and others to match your testing environment.

* [PHPUnit](https://phpunit.de/)

Or you could easily configure your IDE to run these for you.

## Metrics

Project also contains
[PhpMetrics](https://github.com/phpmetrics/phpmetrics)
to make some analyze of your code. You can run this by following command:

```
make phpmetrics
# or alternative
./vendor/bin/phpmetrics --report-html=build/phpmetrics .
```

And after that open `build/phpmetrics/index.html` with your favorite browser.

## Links / resources

* [Symfony Flex set to enable RAD (Rapid Application Development)](https://www.symfony.fi/entry/symfony-flex-to-enable-rad-rapid-application-development)
* [Symfony 4: A quick Demo](https://medium.com/@fabpot/symfony-4-a-quick-demo-da7d32be323)
* [Symfony Development using PhpStorm](http://blog.jetbrains.com/phpstorm/2014/08/symfony-development-using-phpstorm/)
* [Symfony Plugin plugin for PhpStorm](https://plugins.jetbrains.com/plugin/7219-symfony-plugin)
* [PHP Annotations plugin for PhpStorm](https://plugins.jetbrains.com/plugin/7320)
* [Php Inspections (EA Extended) plugin for PhpStorm](https://plugins.jetbrains.com/idea/plugin/7622-php-inspections-ea-extended-)
* [composer-version](https://github.com/vutran/composer-version)

## Authors

[Tarmo Leppänen](https://github.com/tarlepp)

## License

[The MIT License (MIT)](LICENSE)

Copyright (c) 2017 Tarmo Leppänen
