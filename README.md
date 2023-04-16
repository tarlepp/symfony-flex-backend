# What is this?

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![CI](https://github.com/tarlepp/symfony-flex-backend/workflows/CI/badge.svg)](https://github.com/tarlepp/symfony-flex-backend/actions?query=workflow%3ACI)
[![Coverage Status](https://coveralls.io/repos/github/tarlepp/symfony-flex-backend/badge.svg?branch=master)](https://coveralls.io/github/tarlepp/symfony-flex-backend?branch=master)
[![Psalm coverage](https://shepherd.dev/github/tarlepp/symfony-flex-backend/coverage.svg)](https://shepherd.dev/github/tarlepp/symfony-flex-backend)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tarlepp/symfony-flex-backend/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tarlepp/symfony-flex-backend/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/tarlepp/symfony-flex-backend/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)
[![Maintainability](https://api.codeclimate.com/v1/badges/69d6dc6b9fb4791e6b92/maintainability)](https://codeclimate.com/github/tarlepp/symfony-flex-backend/maintainability)
[![Sonarcloud Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=github.com.tarlepp.symfony-flex-backend&metric=alert_status)](https://sonarcloud.io/dashboard?id=github.com.tarlepp.symfony-flex-backend)
[![Sonarcloud Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=github.com.tarlepp.symfony-flex-backend&metric=security_rating)](https://sonarcloud.io/dashboard?id=github.com.tarlepp.symfony-flex-backend)
[![Sonarcloud Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=github.com.tarlepp.symfony-flex-backend&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=github.com.tarlepp.symfony-flex-backend)
[![Sonarcloud Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=github.com.tarlepp.symfony-flex-backend&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=github.com.tarlepp.symfony-flex-backend)

JSON REST API which is build on top of [Symfony](https://symfony.com/)
framework.

This application is mean to use as an `API` that some [frontend](#frontend-table-of-contents)
application(s) or different backend application(s) uses as they like.

## Table of Contents

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Requirements](#requirements-table-of-contents)
    * [Recommendations](#recommendations-table-of-contents)
  * [Installation](#installation-table-of-contents)
    * [1. Clone repository](#1-clone-repository-table-of-contents)
    * [2. Start containers](#2-start-containers-table-of-contents)
    * [3. Using application](#3-using-application-table-of-contents)
    * [4. Getting shell to container](#4-getting-shell-to-container-table-of-contents)
    * [5. Building containers](#5-building-containers-table-of-contents)
  * [Frontend?](#frontend-table-of-contents)
  * [Resources](#resources-table-of-contents)
  * [External links / resources](#external-links--resources-table-of-contents)
  * [Authors](#authors-table-of-contents)
  * [License](#license-table-of-contents)

## Requirements [ᐞ](#table-of-contents)

* [docker-compose](https://docs.docker.com/compose/install/)
* If you are not using docker / docker-compose then follow [this](doc/INSTALLATION_WITHOUT_DOCKER.md)

### Recommendations [ᐞ](#table-of-contents)

* `*nix platform` - most likely you're going to host your application on *nix
  platform - so I would recommend to do development also on that platform.
* `Makefile` support - if you don't have this you need to look `Makefile` file
  to see what each `make` command is doing.

## Installation [ᐞ](#table-of-contents)

This installation guide expects that you're using docker-compose.

### 1. Clone repository [ᐞ](#table-of-contents)

Use your favorite IDE and get checkout from GitHub or just use following
command

```bash
git clone https://github.com/tarlepp/symfony-flex-backend.git
```

### 2. Start containers [ᐞ](#table-of-contents)

For this just run following command, which will start all the containers:

```bash
make start
```

If you like to start containers in background, then you can use following
command:

```bash
make daemon
```

These commands will create following containers to run this backend
application:

* [php](https://www.php.net/) (this is for actual application)
* [nginx](https://www.nginx.com/) (this will serve application)
* [mariadb](https://mariadb.org/) (MariaDB 10.7 which will store all the data
  of application)
* [dozzle](https://dozzle.dev/) (to see your docker container logs)
* [adminer](https://www.adminer.org/) (to manage your database via browser)

### 3. Using application [ᐞ](#table-of-contents)

By default `make start` / `docker-compose up` command starts all the containers
and exposes following ports on `localhost` on your host machine:

* symfony-backend-nginx - [https://localhost:8000](https://localhost:8000) (nginx)
  * PHP-FPM status page -  [https://localhost:8000/status](https://localhost:8000/status)
  * SSL with selfsigned certificates
* symfony-backend-nginx - [http://localhost:8080](http://localhost:8080) (nginx)
  * PHP-FPM status page -  [http://localhost:8080/status](http://localhost:8080/status)
  * Normal HTTP
* symfony-backend-php-fpm - this is not exposed to host machine (php-fpm)
* symfony-backend-mariadb - [http://localhost:33060](http://localhost:33060) (mariadb)
* symfony-backend-dozzle - [http://localhost:8100](http://localhost:8100) (dozzle)
* symfony-backend-adminer - [http://localhost:8200](http://localhost:8200) (adminer)

And this application is usable within your browser on `https://localhost:8000`
address. When you first time open that site you will see "Your connection is
not private" warning - see [this](./docker/nginx/ssl/README.md) to resolve that.

Another choice is to use `http://localhost:8080` which is not using SSL.

MariaDB credentials:

```bash
user: root
password: password
```

### 4. Getting shell to container [ᐞ](#table-of-contents)

After you've run `make start` / `docker-compose up` command you can list all
running containers with `docker ps` command.

And to eg. get shell (bash or fish) access inside one of those containers you
can run following command:

```bash
make bash
```

OR

```bash
make fish
```

### 5. Building containers [ᐞ](#table-of-contents)

For time to time you probably need to build containers again. This is something
that you should do everytime if you have some problems to get containers up and
running. This you can do with following command:

```bash
make daemon-build
```

If you like to see containers logs directly use following command:

```bash
make start-build
```

## Frontend? [ᐞ](#table-of-contents)

So this is an API backend what about frontend then? No worries I've made simple
Angular NgRx powered template frontend which work with this backend just out of
the box.

[Angular NgRx powered frontend template for Symfony backend](https://github.com/tarlepp/angular-ngrx-frontend)

With these two _template_ applications it should be quite easy to start to
build _your_ own application - right?

## Resources [ᐞ](#table-of-contents)

* [Resource index](doc/README.md)
* [Application commands](doc/COMMANDS.md)
* [Concepts and features](doc/CONCEPTS_AND_FEATURES.md)
* [Custom configuration](doc/CUSTOM_CONFIGURATION.md)
* [Development guide](doc/DEVELOPMENT.md)
* [Installation without docker](doc/INSTALLATION_WITHOUT_DOCKER.md)
* [PhpStorm configuration](doc/PHPSTORM.md)
* [Speed problems with docker-compose?](doc/SPEED_UP_DOCKER_COMPOSE.md)
* [Testing guide](doc/TESTING.md)
* [Usage checklist](doc/USAGE_CHECKLIST.md)
* [Using Xdebug](doc/XDEBUG.md)
* [Scripts](scripts/README.md)

## External links / resources [ᐞ](#table-of-contents)

* [Symfony Flex set to enable RAD (Rapid Application Development)](https://www.symfony.fi/entry/symfony-flex-to-enable-rad-rapid-application-development)
* [Symfony 4: A quick Demo](https://medium.com/@fabpot/symfony-4-a-quick-demo-da7d32be323)
* [composer-version](https://github.com/vutran/composer-version)
* [Symfony Recipes Server](https://symfony.sh/)

## Authors [ᐞ](#table-of-contents)

* [Tarmo Leppänen](https://github.com/tarlepp)

## License [ᐞ](#table-of-contents)

[The MIT License (MIT)](LICENSE)

Copyright © 2021 Tarmo Leppänen
