# What is this?
[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![Build Status](https://travis-ci.org/tarlepp/symfony-flex-backend.png?branch=master)](https://travis-ci.org/tarlepp/symfony-flex-backend)
[![Coverage Status](https://coveralls.io/repos/github/tarlepp/symfony-flex-backend/badge.svg?branch=master)](https://coveralls.io/github/tarlepp/symfony-flex-backend?branch=master)

Simple JSON API which is build on top of [Symfony](https://symfony.com/) framework.

Note that this project is built with [Symfony Flex](https://github.com/symfony/flex), so we're using lot `@dev` packages 
at this moment. Also note that we're going to update Symfony itself to `4.x.x` as soon as possible.
 
Table of Contents
=================
 * [What is this?](#what-is-this)
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
 * [Testing](#testing)
 * [Links / resources](#links--resources)
 * [Authors](#authors)
 * [License](#license)

# Requirements
* PHP 7.1.3 or higher
* [Composer](https://getcomposer.org/)
* Database that is supported by [Doctrine](http://www.doctrine-project.org/) 

# Installation 
### 1. Clone repository
Use your favorite IDE and get checkout from GitHub or just use following command
```bash
$ git clone https://github.com/tarlepp/symfony-flex-backend.git
```

### 2. Configuration
Next you need to create `.env` file, which contains all the necessary environment variables that application needs. You
can create it by following command (in folder where you cloned this project):
```bash
$ cp .env.dist .env
```

Then open that file and make necessary changes to it. Note that this `.env` file is ignored on VCS.

### 3. Dependencies installation
Next phase is to install all needed dependencies. This you can do with following command, in your project folder:
```bash
$ composer install
```

Or if you haven't installed composer globally
```bash
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
```

### 4. Create JWT auth keys
Application uses JWT to authenticate users, so we need to create public and private keys to sign those. You can create
new keys with following command.
```bash
$ make generate-jwt-keys
```

### 5. File permissions
Next thing is to make sure that application `var` directory has correct permissions. Instructions for that you can 
find [here](https://symfony.com/doc/current/setup/file_permissions.html).

_I really recommend_ that you use `ACL` option in your development environment.

### 6. Environment checks
To check that your environment is ready for this application. You need to make two checks; 
one for CLI environment and another for your web-server environment.

#### CLI environment
You need to run following command to make all necessary checks.
```bash
$ php ./bin/check.php
```

#### Web-server environment
Open terminal and go to project root directory and run following command to start standalone server.
```bash
$ ./bin/console server:start
```

Open your favorite browser with `http://127.0.0.1:8000/check.php` url and check it for any errors.

##### Apache
To get JWT authorization headers to work correctly you need to make sure that your Apache config has mod_rewrite enabled. This you can do with following command:
```bash
$ sudo a2enmod rewrite
```

### 7. Other (optionally)
#### Allow other IP's to access `dev` environment
If you want to allow another IP addresses or _all_ to your `dev` environment see `/allowed_addresses.php` file for 
detailed information how you can allow certain IP addresses to have access to your `dev` environment.

# Commands
Project contains following console commands to help eg. user management:
```bash
$ ./bin/console user:create         # To create single user
$ ./bin/console user:create-group   # To create single user group
$ ./bin/console user:create-roles   # To initialize user group rolws
$ ./bin/console user:list           # To list current users
$ ./bin/console user:list-groups    # To list current user groups
```

# Testing
Project contains bunch of tests _(Functional, Integration, Unit)_ which you can run simply by following command:
```bash
$ ./vendor/bin/phpunit
```

Note that you need to create `.env.test` file to define your testing environment. This file has the same content as the 
main `.env` file, just change database and others to match your testing environment.

* [PHPUnit](https://phpunit.de/)

Or you could easily configure your IDE to run these for you.

# Links / resources
* [Symfony Flex set to enable RAD (Rapid Application Development)](https://www.symfony.fi/entry/symfony-flex-to-enable-rad-rapid-application-development)
* [Symfony 4: A quick Demo](https://medium.com/@fabpot/symfony-4-a-quick-demo-da7d32be323)
* [Symfony Development using PhpStorm](http://blog.jetbrains.com/phpstorm/2014/08/symfony-development-using-phpstorm/) 
* [PHP Annotations plugin for PhpStorm](https://plugins.jetbrains.com/plugin/7320)
* [Php Inspections (EA Extended) for IntelliJ IDEA](https://plugins.jetbrains.com/idea/plugin/7622-php-inspections-ea-extended-)

# Authors
[Tarmo Leppänen](https://github.com/tarlepp)

# License
[The MIT License (MIT)](LICENSE)

Copyright (c) 2017 Tarmo Leppänen