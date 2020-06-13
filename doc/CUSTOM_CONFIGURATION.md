# What is this?

This document contains necessary information how you can use _your_ own custom
configuration for this application.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Prologue](#prologue)
  * [Creating custom files](#creating-custom-files)
  * [Detailed configuration](#detailed-configuration)
  * [External links / resources](#external-links--resources)

## Prologue

This application is using [Dotenv](https://symfony.com/doc/current/components/dotenv.html)
component parses `.env` files to make environment variables stored in them
accessible via `$_ENV` or `$_SERVER`.

To get basic knowledge how this works you should see [External links / resources](#external-links--resources)
section of this document.

Within this application we define _all_ environment specified settings to
separate `application.json` file that we can easily use with Symfony
configuration part.

## Creating custom files

Starting to use custom configuration is quite easy, all you need to do is
following:

```bash
cp /app/.env /app/.env.local
cp /app/secrets/application.json /app/secrets/application.local.json
sed -i "s/application\.json/application\.local\.json/g" .env.local
```

OR

```bash
make local-configuration
```

With these commands you created _your own_ configuration that application
will use on next start up. So all that you need to do is to make necessary
changes to those newly created files.

Making necessary changes to those files should be quite self explanatory -
just take a look what those files contains. And most likely you only need
to make changes to that `secrets/application.local.json` file.

## Detailed configuration

Below you can see all the current configuration values that are defined in
`application.json` and those are used on Symfony configuration files.

```bash
DATABASE_NAME                     = Self explanatory
DATABASE_URL                      = Self explanatory
JWT_SECRET_KEY                    = Where private JWT key is stored
JWT_PUBLIC_KEY                    = Where public JWT key is stored
JWT_PASSPHRASE                    = Used passphrase for that private key
CORS_ALLOW_ORIGIN                 = Which IP addresses are allowed to make CORS
                                    requests
REQUEST_LOG_SENSITIVE_PROPERTIES  = Which request parameters are "sensitive"
                                    ones, this prevent writing those to request
                                    log
```

## External links / resources

* [Nov 2018 Changes to .env & How to Update](https://symfony.com/doc/current/configuration/dot-env-changes.html)
* [New in Symfony 4.2: Define env vars per environment](https://symfony.com/blog/new-in-symfony-4-2-define-env-vars-per-environment)
* [How to Master and Create new Environments](https://symfony.com/doc/current/configuration/environments.html)
* [Configuring Symfony (and Environments)](https://symfony.com/doc/current/configuration.html)
* [Improvements to the Handling of .env Files for all Symfony Versions](https://symfony.com/blog/improvements-to-the-handling-of-env-files-for-all-symfony-versions)

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
