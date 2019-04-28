# What is this?

This document contains information how you can install this application to local
or dedicated server - without docker support.

## Table of Contents

* [What is this?](#what-is-this)
   * [Table of Contents](#table-of-contents)
   * [Requirements](#requirements)
   * [Installation](#installation)
      * [1. Clone repository](#1-clone-repository)
      * [2. Configuration](#2-configuration)
      * [3. File permissions](#3-file-permissions)
      * [4. Dependencies installation](#4-dependencies-installation)
      * [5. Create JWT auth keys](#5-create-jwt-auth-keys)
      * [6. Environment checks](#6-environment-checks)
         * [CLI environment](#cli-environment)
         * [Web-server environment](#web-server-environment)
            * [Apache](#apache)
      * [7. Database](#7-database)
   * [Notes](#notes)
      * [User password hashing](#user-password-hashing)
         * [Installing libsodium](#installing-libsodium)

## Requirements

* PHP 7.2 or higher
* [Composer](https://getcomposer.org/)
* Database that is supported by [Doctrine](http://www.doctrine-project.org/)
* [Sodium crypto library (libsodium)](https://download.libsodium.org/doc/)
  * for PHP < 7.2 use [this](https://pecl.php.net/package/libsodium) pecl package
  * If you don't want to use [Argon2](https://password-hashing.net/) see [Notes](#notes)

## Installation

### 1. Clone repository

Use your favorite IDE and get checkout from GitHub or just use following command

```bash
git clone https://github.com/tarlepp/symfony-flex-backend.git
```

### 2. Configuration

By default application will use `.env` file for configuration. You can add your
own local file as in `.env.local` and override necessary values there.

Secrets and related settings are by default in `./secrets` directory. By 
default application will use `./secrets/application.json` configuration file.

You can override this by adding `./secrets/application._identifier_.json` and
after that creating a `.env.local` file and use that file in there.

_Note_ that this same works also if you're using [Docker](#installation-with-local-docker-environment)
environment for dev. 

### 3. File permissions

Next thing is to make sure that application `var` directory has correct
permissions. Instructions for that you can find
[here](https://symfony.com/doc/3.4/setup/file_permissions.html).

_I really recommend_ that you use `ACL` option in your development environment.

You can make necessary permission changes with following commands:

```bash
mkdir var
HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
```

### 4. Dependencies installation

Next phase is to install all needed dependencies. This you can do with following
command, in your project folder:

```bash
composer install
```

### 5. Create JWT auth keys

Application uses JWT to authenticate users, so we need to create public and
private keys to sign those. You can create new keys with following command.

```bash
make generate-jwt-keys
```

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

### 7. Database

To initialize database you need to run following commands:

```bash
./bin/console doctrine:database:create
./bin/console doctrine:migrations:migrate
```

## Notes

### User password hashing

By default this application uses [Argon2](https://en.wikipedia.org/wiki/Argon2)
to hash all user passwords, and that is why application requires that your PHP
has support for [Sodium crypto library (libsodium)](https://download.libsodium.org/doc/)
library.

If you cannot or don't want to use this as password hashing method you need to
change [security.yaml](../config/packages/security.yaml#L3-L7) before [installation](#installation)
step.

#### Installing libsodium

To check if your current PHP has libsodium you can use following command

```bash
php -m
```

If _[PHP Modules]_ does not contain `libsodium` you need to install it to get
password hashing to work. You can install it with following commands on your 
*nix box.

```bash
git clone -b stable https://github.com/jedisct1/libsodium.git
cd libsodium && sudo ./configure && sudo make check && sudo make install
pecl install libsodium
```
