# What is this?

<a id="what-is-this"></a>

This document contains basic information how you can use [Xdebug](https://xdebug.org/)
and [PhpStorm](https://www.jetbrains.com/phpstorm/) within this application.

And before you start with this section off documentation you really should read
[PhpStorm configuration](PHPSTORM.md) documentation first - that way you will
have all basic configuration ready.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Configuration and usage](#configuration-and-usage)
      * [PhpStorm basic configuration](#phpstorm-basic-configuration)
      * [First connection](#first-connection)
      * [Configuring debugging server](#configuring-debugging-server)
    * [Debug CLI commands](#debug-cli-commands)
    * [Debug Postman requests](#debug-postman-requests)
    * [External links / resources](#external-links-resources)
    * [Closure](#closure)

## Configuration and usage [ᐞ](#table-of-contents)

<a id="configuration-and-usage"></a>

These instructions relies heavily to screenshots, so you might need to use
your own brains for some parts of these instructions - but I bet you can get
this working in couple of minutes.

### PhpStorm basic configuration [ᐞ](#table-of-contents)

<a id="phpstorm-basic-configuration"></a>

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

<a id="first-connection"></a>

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

<a id="configuring-debugging-server"></a>

Last step is to configure used paths on debugging server to match with your
local paths. See the image below:

![Path mappings](images/xdebug_05.png)

## Debug CLI commands [ᐞ](#table-of-contents)

<a id="debug-cli-commands"></a>

By default this application assumes that you're using `app.localhost` as your
PHP server that you've configured to your IDE. If you need to change that, just
override that in your local `compose.override.yaml` file.

See those [External links / resources](#external-links--resources-table-of-contents)
on this documentation to get more information.

## Debug Postman requests [ᐞ](#table-of-contents)

<a id="debug-postman-requests"></a>

~~If you're using [Postman](https://www.getpostman.com/) to test / debug your
application you need to add `?XDEBUG_SESSION_START=PHPSTORM` to each URL
that you use with Postman.~~

## External links / resources [ᐞ](#table-of-contents)

<a id="external-links-resources"></a>

* [Debugging PHP (web and cli) with Xdebug using Docker and PHPStorm](https://thecodingmachine.io/configuring-xdebug-phpstorm-docker)
* [Debug your PHP in Docker with Intellij/PHPStorm and Xdebug](https://gist.github.com/jehaby/61a89b15571b4bceee2417106e80240d)
* [Debugging with Postman and PHPStorm (Xdebug)](https://www.thinkbean.com/drupal-development-blog/debugging-postman-and-phpstorm-xdebug)

## Closure [ᐞ](#table-of-contents)

<a id="closure"></a>

Happy debugging \o/ - it has not ever be as easy as this...

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
