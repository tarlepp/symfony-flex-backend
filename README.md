# What is this

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![Build Status](https://travis-ci.org/tarlepp/symfony-flex-backend.png?branch=master)](https://travis-ci.org/tarlepp/symfony-flex-backend)
[![Coverage Status](https://coveralls.io/repos/github/tarlepp/symfony-flex-backend/badge.svg?branch=master)](https://coveralls.io/github/tarlepp/symfony-flex-backend?branch=master)
[![Psalm coverage](https://shepherd.dev/github/tarlepp/symfony-flex-backend/coverage.svg)](https://shepherd.dev/github/tarlepp/symfony-flex-backend)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tarlepp/symfony-flex-backend/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tarlepp/symfony-flex-backend/?branch=master)
[![Maintainability](https://api.codeclimate.com/v1/badges/69d6dc6b9fb4791e6b92/maintainability)](https://codeclimate.com/github/tarlepp/symfony-flex-backend/maintainability)
[![Sonarcloud Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=github.com.tarlepp.symfony-flex-backend&metric=alert_status)](https://sonarcloud.io/dashboard?id=github.com.tarlepp.symfony-flex-backend)
[![Sonarcloud Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=github.com.tarlepp.symfony-flex-backend&metric=security_rating)](https://sonarcloud.io/dashboard?id=github.com.tarlepp.symfony-flex-backend)
[![Sonarcloud Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=github.com.tarlepp.symfony-flex-backend&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=github.com.tarlepp.symfony-flex-backend)
[![Sonarcloud Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=github.com.tarlepp.symfony-flex-backend&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=github.com.tarlepp.symfony-flex-backend)
[![SymfonyInsight](https://insight.symfony.com/projects/e59c1ed3-b870-457a-971e-570a27a04784/mini.svg)](https://insight.symfony.com/projects/e59c1ed3-b870-457a-971e-570a27a04784)

JSON REST API which is build on top of [Symfony](https://symfony.com/) framework.

Note that this project is build with [Symfony 4](https://symfony.com/4) and
[Symfony Flex](https://github.com/symfony/flex).

## Table of Contents

* [What is this](#what-is-this)
   * [Table of Contents](#table-of-contents)
   * [Requirements](#requirements)
   * [Installation](#installation)
      * [1. Clone repository](#1-clone-repository)
      * [2. Start containers](#2-start-containers)
      * [3. Using application](#3-using-application)
      * [4. Getting shell to container](#4-getting-shell-to-container)
      * [5. Building containers](#5-building-containers)
   * [Resources](#resources)
   * [External links / resources](#external-links--resources)
   * [Authors](#authors)
   * [License](#license)

## Requirements

* [docker-compose](https://docs.docker.com/compose/install/)
* If you are not using docker / docker-compose then follow [this](doc/INSTALLATION_WITHOUT_DOCKER.md)

## Installation

This installation guide expects that you're using docker-compose.

### 1. Clone repository

Use your favorite IDE and get checkout from GitHub or just use following command

```bash
git clone https://github.com/tarlepp/symfony-flex-backend.git
```

### 2. Start containers

For this just run following command:

```bash
docker-compose up
```

This command will create three (3) containers to run this backend application.
Those containers are following:
 * php (this is for actual application)
 * nginx (this will serve application)
 * mysql (MySQL 5.7 which will store all the data of application)
 
### 3. Using application

By default `docker-compose up` command starts those three containers and 
exposes following ports on `localhost`:
 * 8000 (nginx + php-fpm)
 * 3310 (mysql)
 
And this application is usable within your browser on `http://localhost:8000`
address.

MySQL credentials:

```bash
user: root
password: password
```

### 4. Getting shell to container

After you've run `docker-compose up` command you can list all running containers
with `docker ps` command.

And to eg. get shell access inside one of those containers you can run following
command:

```bash
docker-compose exec php /bin/bash
```

Where that `php` is that actual container where this backend application is
running.

### 5. Building containers

For time to time you probably need to build containers again. This is something
that you should do everytime if you have some problems to get containers up and
running. This you can do with following command:

```bash
docker-compose up --build 
```

## Resources

* [Installation without docker](doc/INSTALLATION_WITHOUT_DOCKER.md)
* [Application commands](doc/COMMANDS.md)
* [Development guide](doc/DEVELOPMENT.md)
* [Testing guide](doc/TESTING.md)
* [Speed problems with docker-compose?](doc/SPEED_UP_DOCKER_COMPOSE.md)
* [Using Xdebug](doc/XDEBUG.md)
* [Custom configuration](doc/CUSTOM_CONFIGURATION.md)

## External links / resources

* [Symfony Flex set to enable RAD (Rapid Application Development)](https://www.symfony.fi/entry/symfony-flex-to-enable-rad-rapid-application-development)
* [Symfony 4: A quick Demo](https://medium.com/@fabpot/symfony-4-a-quick-demo-da7d32be323)
* [Symfony Development using PhpStorm](http://blog.jetbrains.com/phpstorm/2014/08/symfony-development-using-phpstorm/)
* [Symfony Plugin plugin for PhpStorm](https://plugins.jetbrains.com/plugin/7219-symfony-plugin)
* [PHP Annotations plugin for PhpStorm](https://plugins.jetbrains.com/plugin/7320)
* [Php Inspections (EA Extended) plugin for PhpStorm](https://plugins.jetbrains.com/idea/plugin/7622-php-inspections-ea-extended-)
* [EditorConfig](https://plugins.jetbrains.com/plugin/7294-editorconfig)
* [composer-version](https://github.com/vutran/composer-version)
* [Symfony Recipes Server](https://symfony.sh/)

## Authors

[Tarmo Leppänen](https://github.com/tarlepp)

## License

[The MIT License (MIT)](LICENSE)

Copyright © 2019 Tarmo Leppänen
