# What is this?

This document contains information how you can install this application to local
or dedicated server - without using docker.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Requirements](#requirements-table-of-contents)
  * [Installation](#installation-table-of-contents)
    * [1. Clone repository](#1-clone-repository-table-of-contents)
    * [2. Configuration](#2-configuration-table-of-contents)
    * [3. File permissions](#3-file-permissions-table-of-contents)
    * [4. Dependencies installation](#4-dependencies-installation-table-of-contents)
    * [5. Create JWT auth keys](#5-create-jwt-auth-keys-table-of-contents)
    * [6. Environment checks](#6-environment-checks-table-of-contents)
      * [CLI environment](#cli-environment-table-of-contents)
      * [Web-server environment](#web-server-environment-table-of-contents)
        * [Apache](#apache-table-of-contents)
    * [7. Database](#7-database-table-of-contents)

## Requirements [ᐞ](#table-of-contents)

* PHP 7.4 or higher
* [Composer](https://getcomposer.org/)
* Database that is supported by [Doctrine](http://www.doctrine-project.org/)

## Installation [ᐞ](#table-of-contents)

### 1. Clone repository [ᐞ](#table-of-contents)

Use your favorite IDE and get checkout from GitHub or just use following command

```bash
git clone https://github.com/tarlepp/symfony-flex-backend.git
```

### 2. Configuration [ᐞ](#table-of-contents)

By default application will use `.env` file for configuration. You can add your
own local file as in `.env.local` and override necessary values there.

Secrets and related settings are by default in `./secrets` directory. By
default application will use `./secrets/application.json` configuration file.

You can override this by adding `./secrets/application._identifier_.json` and
after that creating a `.env.local` file and use that file in there.

_Note_ that this same works also if you're using [Docker](../README.md#2-start-containers-table-of-contents)
environment for dev.

### 3. File permissions [ᐞ](#table-of-contents)

Next thing is to make sure that application `var` directory has correct
permissions. Instructions for that you can find
[here](https://symfony.com/doc/3.4/setup/file_permissions.html).

_I really recommend_ that you use `ACL` option in your development environment.

You can make necessary permission changes with following commands:

```bash
mkdir var
HTTPDUSER=`ps axo user,comm | \
    grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | \
    grep -v root | head -1 | cut -d\  -f1`
setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
```

### 4. Dependencies installation [ᐞ](#table-of-contents)

Next phase is to install all needed dependencies. This you can do with following
command, in your project folder:

```bash
composer install
```

### 5. Create JWT auth keys [ᐞ](#table-of-contents)

Application uses JWT to authenticate users, so we need to create public and
private keys to sign those. You can create new keys with following command.

```bash
make generate-jwt-keys
```

### 6. Environment checks [ᐞ](#table-of-contents)

To check that your environment is ready for this application. You need to make
two checks; one for CLI environment and another for your web-server environment.

#### CLI environment [ᐞ](#table-of-contents)

You need to run following command to make all necessary checks.

```bash
./vendor/bin/requirements-checker
```

#### Web-server environment [ᐞ](#table-of-contents)

Open terminal and go to project root directory and run following command to
start standalone server.

```bash
./bin/console server:start
```

Open your favorite browser with `http://127.0.0.1:8000/check.php` url and
check it for any errors.

##### Apache [ᐞ](#table-of-contents)

To get JWT authorization headers to work correctly you need to make sure that
your Apache config has mod_rewrite enabled. This you can do with following
command:

```bash
sudo a2enmod rewrite
```

### 7. Database [ᐞ](#table-of-contents)

To initialize database you need to run following commands:

```bash
./bin/console doctrine:database:create
./bin/console doctrine:migrations:migrate
```

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
