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

---

[Back to previous](../README.md)
