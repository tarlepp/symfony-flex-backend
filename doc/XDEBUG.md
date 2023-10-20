# What is this?

This document contains basic information how you can use [Xdebug](https://xdebug.org/)
and [PhpStorm](https://www.jetbrains.com/phpstorm/) within this application.

And before you start with this section off documentation you really should read
[PhpStorm configuration](PHPSTORM.md) documentation first - that way you will
have all basic configuration ready.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Configuration and usage](#configuration-and-usage-table-of-contents)
    * [PhpStorm basic configuration](#phpstorm-basic-configuration-table-of-contents)
    * [First connection](#first-connection-table-of-contents)
    * [Configuring debugging server](#configuring-debugging-server-table-of-contents)
  * [Debug CLI commands](#debug-cli-commands-table-of-contents)
  * [Debug Postman requests](#debug-postman-requests-table-of-contents)
  * [External links / resources](#external-links--resources-table-of-contents)
  * [Closure](#closure-table-of-contents)

## Configuration and usage [ᐞ](#table-of-contents)

These instructions relies heavily to screenshots, so you might need to use
your own brains for some parts of these instructions - but I bet you can get
this working in couple of minutes.

### PhpStorm basic configuration [ᐞ](#table-of-contents)

1) Make sure that Xdebug port is `9003`
2) Validate debugger configuration
3) Install needed browser extensions

You can check all those within screen as below:

![Basic settings](images/xdebug_01.png)

Note that validation screen should look like image below:

![Validation](images/xdebug_02.png)

Create Run/Debug Configuration like in image below:

![Run/Debug Configuration](images/xdebug_03.png)

### First connection [ᐞ](#table-of-contents)

After you have make sure that all basic things are configured properly you can
start to listen incoming PHP debug connections. After this you need to do
following:

1) Add breakpoint to your code
2) Enable Xdebug in your browser
3) Reload browser page

After that you should see following:

![Incoming connection from Xdebug](images/xdebug_04.png)

And in this screen select the correct `index.php` file.

### Configuring debugging server [ᐞ](#table-of-contents)

Last step is to configure used paths on debugging server to match with your
local paths. See the image below:

![Path mappings](images/xdebug_05.png)

## Debug CLI commands [ᐞ](#table-of-contents)

By default this application assumes that you're using `app.localhost` as your
PHP server that you've configured to your IDE. If you need to change that, just
override that in your local `docker-compose.override.yml` file.

See those [External links / resources](#external-links--resources-table-of-contents)
on this documentation to get more information.

## Debug Postman requests [ᐞ](#table-of-contents)

~~If you're using [Postman](https://www.getpostman.com/) to test / debug your
application you need to add `?XDEBUG_SESSION_START=PHPSTORM` to each URL
that you use with Postman.~~

## External links / resources [ᐞ](#table-of-contents)

* [Debugging PHP (web and cli) with Xdebug using Docker and PHPStorm](https://thecodingmachine.io/configuring-xdebug-phpstorm-docker)
* [Debug your PHP in Docker with Intellij/PHPStorm and Xdebug](https://gist.github.com/jehaby/61a89b15571b4bceee2417106e80240d)
* [Debugging with Postman and PHPStorm (Xdebug)](https://www.thinkbean.com/drupal-development-blog/debugging-postman-and-phpstorm-xdebug)

## Closure [ᐞ](#table-of-contents)

Happy debugging \o/ - it has not ever be as easy as this...

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
