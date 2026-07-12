# What is this?

<a id="what-is-this"></a>

This document contains information about how you can speed up Docker Engine
usage in development stage.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Reasons?](#reasons)
    * [Environments with problems](#environments-with-problems)
      * [Windows](#windows)
      * [Mac](#mac)
      * [Linux](#linux)
    * [Installation of docker-sync](#installation-of-docker-sync)
      * [Configuration](#configuration)
      * [Startup](#startup)
    * [Notes](#notes)

## Reasons? [ᐞ](#table-of-contents)

<a id="reasons"></a>

Symfony generates quite lot of cache files, because it's using compiled
container to make things happen. This means a quite lot of IO traffic and that
causes slowness problems within certain environments.

## Environments with problems [ᐞ](#table-of-contents)

<a id="environments-with-problems"></a>

Basically Windows and Mac; with linux you should not have these problems at all.

### Windows [ᐞ](#table-of-contents)

<a id="windows"></a>

The "most" clean solution to solve this atm is to run eg. Ubuntu desktop within
[VMware](https://www.vmware.com/) / [VirtualBox](https://www.virtualbox.org/)
machines. And this means that you actually run your favorite IDE inside that
virtual machine.

Another way is to use [docker-sync](#installation-of-docker-sync-table-of-contents).
Application itself already contains necessary [docker-sync.yml](../docker-sync.yml)
configuration  file to help with this.

### Mac [ᐞ](#table-of-contents)

<a id="mac"></a>

With Mac there is a bit speed difference versus pure _*inux_ installation, but
you could try to speed that up by using [Docker for Mac Edge](https://docs.docker.com/docker-for-mac/edge-release-notes/)

Some benchmark about `Docker for Mac` versus `Docker for Mac Edge`:
[Docker for Mac performance benchmark](https://medium.com/@somwhatparanoid/tweaking-docker-for-mac-performance-for-php-and-symfony-b63f3395a1da)

And if that [Docker for Mac Edge](https://docs.docker.com/docker-for-mac/edge-release-notes/)
isn't fast enough for you, you could also setup that [docker-sync](#installation-of-docker-sync-table-of-contents)
for your environment.

### Linux [ᐞ](#table-of-contents)

<a id="linux"></a>

No need to do anything `¯\_(ツ)_/¯`

## Installation of docker-sync [ᐞ](#table-of-contents)

<a id="installation-of-docker-sync"></a>

Follow install instructions from [docker-sync](http://docker-sync.io/)
website.

### Configuration [ᐞ](#table-of-contents)

<a id="configuration"></a>

Create a `compose.override.yaml` file with following content:

```yaml
#
# This file should NOT be added to your VCS, only purpose of this is to
# override those volumes with docker-sync.yml config
#
services:
    php:
        volumes:
            - backend-code:/app:cached
            - /app/var/
    nginx:
        volumes:
            - backend-code:/app:cached
            - /app/var/
volumes:
    backend-code:
        external: true
```

### Startup [ᐞ](#table-of-contents)

<a id="startup"></a>

To start application you just need to use command `docker-sync-stack start`

## Notes [ᐞ](#table-of-contents)

<a id="notes"></a>

If / when you want to use Xdebug, you should read this document:
[Using Xdebug](XDEBUG.md)

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
