# What is this?

<a id="what-is-this"></a>

This project includes a Dev Container definition in `.devcontainer/`.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [What it uses](#what-it-uses)
    * [How to use](#how-to-use)
    * [UID/GID mapping](#uidgid-mapping)
    * [Services started by default](#services-started-by-default)
    * [Port mappings](#port-mappings)

## What it uses [ᐞ](#table-of-contents)

<a id="what-it-uses"></a>

* Existing root `compose.yaml`
* Existing root `Dockerfile_dev`
* A small Compose override: `.devcontainer/docker-compose.devcontainer.yml`

## How to use [ᐞ](#table-of-contents)

<a id="how-to-use"></a>

1. Open the repository in VS Code.
2. Run **Dev Containers: Reopen in Container**.
3. The container attaches to the `php` service with workspace at `/app`.

## UID/GID mapping [ᐞ](#table-of-contents)

<a id="uidgid-mapping"></a>

The dev container override sets defaults for required Compose variables:

* `HOST_UID` defaults to `1000`
* `HOST_GID` defaults to `1000`

If your host user is different, export variables before launching VS Code:

```bash
export HOST_UID="$(id -u)"
export HOST_GID="$(id -g)"
code /home/wnd/PhpstormProjects/symfony-flex-backend
```

## Services started by default [ᐞ](#table-of-contents)

<a id="services-started-by-default"></a>

* `php`
* `mariadb`
* `nginx`
* `dozzle`
* `adminer`

## Port mappings [ᐞ](#table-of-contents)

<a id="port-mappings"></a>

| Service | Host / Forwarded port | URL |
| --- | --- | --- |
| Nginx (HTTPS) | `8000` | `https://localhost:8000` |
| Nginx (HTTP) | `8080` | `http://localhost:8080` |
| MariaDB | `33060` | `localhost:33060` |
| Dozzle | `8100` | `http://localhost:8100` |
| Adminer | `8200` | `http://localhost:8200` |

You can still use existing Make targets from inside the container terminal.

---

[Back to previous](../README.md)
