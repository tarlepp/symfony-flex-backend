# What is this?

<a id="what-is-this"></a>

[fzf](https://github.com/junegunn/fzf) is a command-line fuzzy finder. The PHP
development image includes Debian's `fzf` package for selecting files,
searching shell history, and navigating directories in Bash and Fish.
It is available in regular Compose shells, IDE Dev Containers, and the CI
development image, but is not installed in the production image.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Setup](#setup)
    * [Shell shortcuts](#shell-shortcuts)
    * [Usage examples](#usage-examples)
    * [Troubleshooting](#troubleshooting)

## Setup [ᐞ](#table-of-contents)

<a id="setup"></a>

No host installation or additional shell plugin is required. From the
repository root on the Docker host, rebuild the PHP development image:

```bash
docker compose build php
```

When ready to replace the running PHP container, run the following on the
Docker host. This interrupts PHP service and runs the development entrypoint,
which can install dependencies, generate missing JWT keys, and apply database
migrations. Use only with your intended development database.

```bash
docker compose up -d --no-deps php
```

For IDE Dev Containers, use your IDE's rebuild action instead; see the
[Dev Container guide](../.devcontainer/README.md#how-to-use).

From the repository root on the Docker host, open Bash:

```bash
make bash
```

For Fish, use this host command instead:

```bash
make fish
```

Inside the PHP development container, check the installed version:

```bash
fzf --version
```

The version follows the Debian package available to the image, not necessarily
the latest upstream release. Bash loads the packaged key-binding and completion
scripts. Fish loads the package's
`fzf_key_bindings` function in interactive shells. Do not add upstream
`fzf --bash` or `fzf --fish` initialization: those options require newer
versions than Debian Bookworm provides.

## Shell shortcuts [ᐞ](#table-of-contents)

<a id="shell-shortcuts"></a>

These shortcuts are enabled in interactive Bash and Fish shells:

| Shortcut | Action |
| --- | --- |
| `Ctrl-T` | Select files or directories and insert their paths |
| `Ctrl-R` | Search shell history and insert a selected command |
| `Alt-C` | Select a directory and change to it |

Type part of a name to narrow the results. Use the arrow keys to move,
`Enter` to select, and `Esc` or `Ctrl-C` to cancel. History search uses the
current shell's history; it does not combine host, Bash, and Fish histories.
Bash also supports fuzzy path completion, for example typing `vim **` and
pressing `Tab`.

## Usage examples [ᐞ](#table-of-contents)

<a id="usage-examples"></a>

Run these examples from `/app` inside the PHP development container, after
opening a shell as described above. They work in both Bash and Fish.

Select a tracked repository file without searching dependencies or secrets:

```bash
git ls-files | fzf
```

Preview tracked files while selecting:

```bash
git ls-files | fzf --preview 'head -n 80 -- {}'
```

Select several files using `Tab`, then print their paths with `Enter`:

```bash
git ls-files | fzf --multi
```

For a noninteractive smoke check, run this from the repository root on the
Docker host; the expected output is `Dockerfile`:

```bash
docker compose exec -T php bash -c \
    "printf '%s\n' Dockerfile compose.yaml | fzf --filter=Dockerfile"
```

## Troubleshooting [ᐞ](#table-of-contents)

<a id="troubleshooting"></a>

If `fzf` is missing, rebuild and recreate the PHP container using the
[setup instructions](#setup). Rebuilding an image alone does not update an
already running container.

If shortcuts do not work, open a fresh interactive shell with `make bash` or
`make fish` on the Docker host. Ensure the terminal or IDE is not intercepting
the shortcut. Noninteractive `docker compose exec -T` commands do not load
interactive key bindings and should use `--filter` rather than the picker.

If an upstream example reports an unknown option, check `fzf --help` inside
the container for the options supported by the installed Debian package.

---

[Back to previous](README.md)
