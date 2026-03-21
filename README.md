<a id="what-is-this"></a>

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
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/tarlepp/symfony-flex-backend/badge)](https://securityscorecards.dev/viewer/?platform=github.com&org=tarlepp&repo=symfony-flex-backend)

JSON REST API which is built on top of [Symfony](https://symfony.com/)
framework.

This application is meant to be used as an `API` that frontend applications or
different backend applications can use as needed. One example frontend is
[this Angular template](https://github.com/tarlepp/angular-ngrx-frontend),
though you can use any frontend solution.

### Quick Start

```bash
git clone https://github.com/tarlepp/symfony-flex-backend.git
cd symfony-flex-backend
make start
```

Then open `https://localhost:8000` in your browser. For more details, see
[Installation](#installation).

<a id="table-of-contents"></a>

## Table of Contents

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Requirements](#requirements)
    * [Recommendations](#recommendations)
  * [Installation](#installation)
    * [1. Clone repository](#step-1-clone-repository)
    * [2. Start containers](#step-2-start-containers)
  * [Running the application](#running-the-application)
    * [Using application](#using-application)
    * [Getting shell to container](#getting-shell-to-container)
    * [Building containers](#building-containers)
    * [Health check](#health-check)
  * [Dev Containers](#dev-containers)
  * [Frontend?](#frontend)
* [Resources](#resources)
  * [External links / resources](#external-links--resources)
  * [Authors](#authors)
  * [License](#license)

<a id="requirements"></a>

## Requirements [ᐞ](#table-of-contents)

* [Docker Engine](https://docs.docker.com/engine/install/)
* [Docker Compose v2](https://docs.docker.com/compose/install/) (included with Docker Desktop)

If you are not using Docker Engine, follow [this](doc/INSTALLATION_WITHOUT_DOCKER.md).

<a id="recommendations"></a>

### Recommendations [ᐞ](#table-of-contents)

* `*nix platform` - most likely you're going to host your application on *nix
  platform - so I would recommend to do development also on that platform.
* `WSL2 on Windows` - if you develop on Windows, use WSL2 to get a Linux-like
  development environment for Docker and tooling.
* `Dev Container capable IDE` - recommended for the quickest setup (e.g. VS Code
  or JetBrains IDEs with Dev Container support).
* `Makefile` support - recommended if you run containers with Make commands;
  if you do not have `make`, check `Makefile` to see equivalent Docker
  commands.

<a id="installation"></a>

## Installation [ᐞ](#table-of-contents)

This installation guide expects that you're using Docker Engine.

<a id="step-1-clone-repository"></a>

### 1. Clone repository [ᐞ](#table-of-contents)

Use your favorite IDE and check out the repository from GitHub, or use the
following command:

```bash
git clone https://github.com/tarlepp/symfony-flex-backend.git
```

<a id="step-2-start-containers"></a>

### 2. Start containers [ᐞ](#table-of-contents)

You can run this project either with Dev Containers (for example in VS Code or
JetBrains IDEs) or with Make commands.

For Dev Containers, open this repository in your IDE and use its Dev Container
workflow to reopen/start the project in a container (see
[Dev Containers](#dev-containers) section for details).

If you want to use Make commands instead, run the following command, which will
start all the containers:

```bash
make start
```

If you want to start containers in the background, use the following command:

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

For next steps (application URLs, shell access, and rebuilding containers),
see [Running the application](#running-the-application).

<a id="running-the-application"></a>

## Running the application [ᐞ](#table-of-contents)

These instructions are shared for both Make-based and Dev Container-based
workflows.

<a id="using-application"></a>

### Using application [ᐞ](#table-of-contents)

When containers are running (either via Make commands or Dev Containers),
following ports are exposed on `localhost` on your host machine:

* symfony-backend-nginx - [https://localhost:8000](https://localhost:8000) (nginx)
  * PHP-FPM status page -  [https://localhost:8000/status](https://localhost:8000/status)
  * SSL with self-signed certificates
* symfony-backend-nginx - [http://localhost:8080](http://localhost:8080) (nginx)
  * PHP-FPM status page -  [http://localhost:8080/status](http://localhost:8080/status)
  * Normal HTTP
* symfony-backend-php-fpm - this is not exposed to host machine (php-fpm)
* symfony-backend-mariadb - `localhost:33060` (mariadb)
* symfony-backend-dozzle - [http://localhost:8100](http://localhost:8100) (dozzle)
* symfony-backend-adminer - [http://localhost:8200](http://localhost:8200) (adminer)

And this application is usable in your browser at `https://localhost:8000`.
When you open that site for the first time, you will see a "Your connection is
not private" warning - see [this](./docker/nginx/ssl/README.md) to resolve that.

Another choice is to use `http://localhost:8080`, which does not use SSL.

MariaDB credentials:

```bash
user: root
password: password
```

**Note:** These credentials are for local development only and should never be
used in production.

<a id="getting-shell-to-container"></a>

### Getting shell to container [ᐞ](#table-of-contents)

After you've started containers (`make start` / `make daemon` or via Dev
Containers), you can list all running containers with `docker ps`.

To get shell (bash or fish) access inside one of those containers, run the
following command:

```bash
make bash
```

or

```bash
make fish
```

If you are using Dev Containers, you can also use the IDE terminal that is
already attached to the `php` container.

<a id="building-containers"></a>

### Building containers [ᐞ](#table-of-contents)

From time to time you probably need to build containers again. This is something
that you should do every time if you have some problems getting containers up
and running.

If you use Make commands, rebuild/start containers with:

```bash
make daemon-build
```

If you want to see container logs directly, use the following command:

```bash
make start-build
```

If you use Dev Containers, use your IDE's Dev Container rebuild action (for
example, "Rebuild Container" / "Rebuild and Reopen in Container").

If you prefer CLI, you can rebuild with:

```bash
docker compose -f compose.yaml -f .devcontainer/docker-compose.devcontainer.yml build
docker compose -f compose.yaml -f .devcontainer/docker-compose.devcontainer.yml up -d php mariadb nginx dozzle adminer
```

<a id="health-check"></a>

### Health check [ᐞ](#table-of-contents)

To verify your setup is working correctly, you can check:

```bash
# Check container status
docker compose ps

# Test HTTP endpoint
curl -I http://localhost:8080

# Test HTTPS endpoint (ignore SSL warning)
curl -k -I https://localhost:8000
```

Alternatively, you can use **Dozzle** (container log viewer) to monitor all services:

* Open [http://localhost:8100](http://localhost:8100) in your browser
* View real-time logs for all running containers
* Check container status and resource usage

<a id="dev-containers"></a>

## Dev Containers [ᐞ](#table-of-contents)

This project also includes a Dev Container setup in `.devcontainer/` that can
be used from VS Code and JetBrains IDEs with Dev Container support.

When you reopen the repository in a container, it starts the full Docker
Compose stack:

* `php`
* `nginx`
* `mariadb`
* `dozzle`
* `adminer`

For application URLs, shell access, and rebuild commands, see
[Running the application](#running-the-application).

For detailed usage, UID/GID notes, and port mappings, see
[`.devcontainer/README.md`](.devcontainer/README.md).

<a id="frontend"></a>

## Frontend? [ᐞ](#table-of-contents)

This backend API can be consumed by any frontend technology or framework. You
can use React, Vue, Angular, Svelte, or any other frontend solution that can
make HTTP requests.

As a reference example, I've created an [Angular NgRx powered frontend template
project](https://github.com/tarlepp/angular-ngrx-frontend) that works with this
backend. It demonstrates how to integrate with this API, but you're free to use
any technology stack that fits your project needs.

With this backend, it should be quite easy to start building _your_ own
application with the frontend technology of your choice.

<a id="resources"></a>

## Resources [ᐞ](#table-of-contents)

* [Resource index](doc/README.md)
* [Application commands](doc/COMMANDS.md)
* [Concepts and features](doc/CONCEPTS_AND_FEATURES.md)
* [Custom configuration](doc/CUSTOM_CONFIGURATION.md)
* [Development guide](doc/DEVELOPMENT.md)
* [Installation without docker](doc/INSTALLATION_WITHOUT_DOCKER.md)
* [PhpStorm configuration](doc/PHPSTORM.md)
* [Speed problems with Docker Engine?](doc/SPEED_UP_DOCKER_COMPOSE.md)
* [Testing guide](doc/TESTING.md)
* [Usage checklist](doc/USAGE_CHECKLIST.md)
* [Using Xdebug](doc/XDEBUG.md)
* [Scripts](scripts/README.md)

<a id="external-links--resources"></a>

## External links / resources [ᐞ](#table-of-contents)

* [Symfony Flex set to enable RAD (Rapid Application Development)](https://www.symfony.fi/entry/symfony-flex-to-enable-rad-rapid-application-development)
* [Symfony 4: A quick Demo](https://medium.com/@fabpot/symfony-4-a-quick-demo-da7d32be323)
* [composer-version](https://github.com/vutran/composer-version)
* [Symfony Recipes Server](https://symfony.sh/)

<a id="authors"></a>

## Authors [ᐞ](#table-of-contents)

* [Tarmo Leppänen](https://github.com/tarlepp)

<a id="license"></a>

## License [ᐞ](#table-of-contents)

[The MIT License (MIT)](LICENSE)

Copyright © 2024 Tarmo Leppänen
