# What is this?

<a id="what-is-this"></a>

This project includes a Dev Container definition in `.devcontainer/`.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [What it uses](#what-it-uses)
    * [How to use](#how-to-use)
    * [Python tooling](#python-tooling)
    * [UID/GID mapping](#uidgid-mapping)
    * [Services started by default](#services-started-by-default)
    * [Port mappings](#port-mappings)

## What it uses [ᐞ](#table-of-contents)

<a id="what-it-uses"></a>

* Existing root `compose.yaml`
* The `development` target in the root `Dockerfile`, selected by `compose.yaml`
* A small Compose override: `.devcontainer/docker-compose.devcontainer.yml`

## How to use [ᐞ](#table-of-contents)

<a id="how-to-use"></a>

1. Open the repository in VS Code.
2. Run **Dev Containers: Reopen in Container**.
3. The container attaches to the `php` service with workspace at `/app`.

## Python tooling [ᐞ](#table-of-contents)

<a id="python-tooling"></a>

The shared `development` image explicitly installs Debian's `python3` and
`python3-venv` packages. This applies to both Dev Containers and the regular
Compose development environment; Python is not added to the production image.
The default Python interpreter is in `/home/dev/.venv`, owned by `dev`.
`VIRTUAL_ENV` and `PATH` are configured in the image, so `python`, `python3`,
and `python -m pip` use this environment automatically in `make bash`,
`make fish`, IDE Dev Container terminals, and `docker compose exec php`.
No shell-specific activation or host Python installation is needed.

Run all repository Python commands **inside the PHP development container**,
not in a host terminal. From the host, first open a shell with `make bash` or
`make fish`; an IDE Dev Container terminal is already inside the container.
Then run the repository's standard-library documentation checker from `/app`:

```bash
python3 --version
python3 -c 'import sys; print(sys.executable); print(sys.prefix)'
python3 scripts/check-skills.py
```

The interpreter and prefix should point to `/home/dev/.venv`. To run the
checker from the host without an interactive shell, use Docker explicitly:

```bash
docker compose exec -T php python3 scripts/check-skills.py
```

Alternatively, run `make check-skills` from either the host or the container.
The Make target always executes the checker inside Docker.

For third-party packages, run `python -m pip install <package>` inside the
container to install into the default development venv. These installations
are container-local and are lost when the container is recreated; required
shared tooling belongs in the Dockerfile.

Do not install packages into Debian's system Python with `sudo pip`,
`pip --user`, or `--break-system-packages`. The existing `thefuck` shell helper
is installed in its own virtual environment at
`/home/dev/.local/share/thefuck`, with its command linked into
`/home/dev/.local/bin` for Bash and Fish.

The documentation CI job also uses `make check-skills`. On the CI runner this
builds and runs the existing Compose `php` development service as a temporary
container, without starting dependencies or running the application entrypoint.
No separate documentation image or Compose configuration is needed.

Rebuild the development image after pulling this change. Use your IDE's
Dev Container rebuild action, or follow the root
[container rebuild instructions](../README.md#building-containers).

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
