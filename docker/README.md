# What is this?

<a id="what-is-this"></a>

This folder contains necessary configuration files for Docker containers.
For fuzzy file selection and shell history search in the development container,
see the [fzf guide](../doc/FZF.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Python development dependencies](#python-development-dependencies)
    * [Node toolchain build arguments](#node-toolchain-build-arguments)

## Python development dependencies [ᐞ](#table-of-contents)

<a id="python-development-dependencies"></a>

The root [`requirements.txt`](../requirements.txt) declares the development
container's Python dependencies. PyYAML, setuptools, and thefuck share the
`/home/dev/.venv` virtual environment, which is on `PATH` for both interactive
shells and noninteractive tooling. System Python remains unchanged.

Update package pins in `requirements.txt`; dependency updates do not require
editing `Dockerfile`. The manifest is copied before installation so changes
invalidate the Python installation layer. These packages are installed only
in the development image, not the maintenance or production images.

To apply updates, run from the repository root on the Docker host:

```bash
docker compose build php
docker compose up -d --no-deps php
```

The second command recreates the PHP service when its image changes. Its
development entrypoint can install Composer dependencies, generate missing JWT
keys, and run database migrations; coordinate this restart in shared environments.

## Node toolchain build arguments [ᐞ](#table-of-contents)

<a id="node-toolchain-build-arguments"></a>

The root `Dockerfile` declares `NVM_VERSION`, `NVM_INSTALLER_SHA256`, and
`NODE_VERSION` together at the top. Their pinned defaults can be overridden
with build arguments. Only the development stage imports them; maintenance
and production do not install this toolchain.
See [toolchain versions and checksums](../doc/DOCKER_MULTI_PLATFORM.md#development-toolchain-versions-and-checksums)
for how these settings differ from Docker image pins and the scope of the
image update commands.

The nvm installer is downloaded to a file and its SHA-256 checksum is checked
before execution. Update `NVM_VERSION` and `NVM_INSTALLER_SHA256` together
when changing nvm. Use a checksum verified against the intended upstream
release, not a value supplied by an untrusted build caller. Node downloads
use nvm's built-in checksum verification. Neither check is a signature check.

To build a separate development image using the default toolchain, run from
the repository root on the Docker host:

```bash
docker build --target development \
  --build-arg HOST_UID="$(id -u)" \
  --build-arg HOST_GID="$(id -g)" \
  --build-arg NVM_VERSION=v0.40.8 \
  --build-arg NVM_INSTALLER_SHA256=48a0eee9a60e07422dce0eb5774754c83889570ca1ee2566c516acbe8af03a9e \
  --build-arg NODE_VERSION=26.12.1 \
  --tag symfony-flex-backend:custom-node-toolchain .
```

Omit the three toolchain arguments to use the Dockerfile defaults. Versions
are selected at build time; changing runtime environment variables does not
reinstall nvm or Node. This command builds an image without recreating services
or running application entrypoints.

---

[Back to previous](../README.md)
