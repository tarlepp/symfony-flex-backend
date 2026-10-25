# What is this?

<a id="what-is-this"></a>

This page records the status of running the application without Docker.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Status](#status)
  * [Requirements](#requirements)
  * [Recommended setup](#recommended-setup)

## Status [ᐞ](#table-of-contents)

<a id="status"></a>

**A standalone installation is not currently maintained or supported.** The
previous instructions referenced obsolete PHP and Symfony commands and should
not be used. In particular, they did not provide a validated way to install
the current application dependencies, configure the database, or generate JWT
keys outside the PHP development container.

Use the Docker Compose setup in the [main README](../README.md) for development.
For a local production-mode check, see
[Running production locally](DOCKER_MULTI_PLATFORM.md#running-production-locally);
this is a Docker-based local workflow, not a production deployment guide.

## Requirements [ᐞ](#requirements)

<a id="requirements"></a>

The current Composer configuration requires PHP `^8.5.12`. The Docker
development and production environments provide the application runtime and
extensions. The bundled MariaDB image is defined in
[`docker/mariadb/Dockerfile`](../docker/mariadb/Dockerfile).

These details do not constitute a supported standalone installation
procedure. If a non-Docker setup is needed, it must be developed and validated
against the current Composer requirements, Symfony configuration, database
version, web-server front controller, and JWT-key configuration before this
page can be replaced with setup instructions.

## Recommended setup [ᐞ](#table-of-contents)

<a id="recommended-setup"></a>

Follow the [main README installation instructions](../README.md#installation)
to start the development environment with Docker Compose.

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
