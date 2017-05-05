# What is this?
[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![Build Status](https://travis-ci.org/tarlepp/symfony-flex-backend.png?branch=master)](https://travis-ci.org/tarlepp/symfony-flex-backend)
[![Coverage Status](https://coveralls.io/repos/github/tarlepp/symfony-flex-backend/badge.svg?branch=master)](https://coveralls.io/github/tarlepp/symfony-flex-backend?branch=master)

Simple JSON API which is build on top of [Symfony](https://symfony.com/) framework.

Note that this project is built with [Symfony Flex](https://github.com/symfony/flex), so we're using lot `@dev` packages 
at this moment. Also note that we're going to update Symfony itself to `4.x.x` as soon as possible. 

# Requirements
* PHP 7.1
* [Composer](https://getcomposer.org/)
* Database that is supported by [Doctrine](http://www.doctrine-project.org/) 

# Installation 
### 1. Clone repository
Use your favorite IDE and get checkout from git OR just use following command

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

# Links / resources
[Symfony Flex set to enable RAD (Rapid Application Development)](https://www.symfony.fi/entry/symfony-flex-to-enable-rad-rapid-application-development)
[Symfony 4: A quick Demo](https://medium.com/@fabpot/symfony-4-a-quick-demo-da7d32be323)

# Authors
[Tarmo Leppänen](https://github.com/tarlepp)

# LICENSE
[The MIT License (MIT)](LICENSE)

Copyright (c) 2017 Tarmo Leppänen