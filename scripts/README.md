# What is this?

<a id="what-is-this"></a>

This directory contains different scripts that are used during development.

<a id="table-of-contents"></a>

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Resources](#resources)
      * [Project stats script](#project-stats-script)
      * [GitHub Actions update checker](#github-actions-update-checker)
      * [Docker multi-platform image helper](#docker-multi-platform-image-helper)

## Resources [ᐞ](#table-of-contents)

<a id="resources"></a>

* [Project stats script](project-stats.sh)
  * This script is used to generate simple project stats. It will generate
    output with some basic stats about project.
* [GitHub Actions update checker](check-action-updates.sh)
  * Checks pinned GitHub Actions from `.github/workflows/*.yml`, reports
    discovery issues (unpinned refs/conflicting versions), and checks for
    available updates in the current major version line.
* [Docker multi-platform image helper](docker-images.sh)
  * Resolves multi-platform image digests, verifies pinned base images and
    builds images for other platforms.

### Project stats script [ᐞ](#table-of-contents)

<a id="project-stats-script"></a>

File: `scripts/project-stats.sh`

```bash
make project-stats
```

If you are already inside the container, you can still run
`bash scripts/project-stats.sh` directly.

### GitHub Actions update checker [ᐞ](#table-of-contents)

<a id="github-actions-update-checker"></a>

File: `scripts/check-action-updates.sh`

```bash
make check-action-updates
```

If you are already inside the container, you can still run
`bash scripts/check-action-updates.sh` directly.

Print current pins as markdown:

```bash
bash scripts/check-action-updates.sh --current-pins-md
```

Exit codes:

* `0` = no discovery issues and no updates found
* `1` = updates available
* `2` = discovery issues found (for example unpinned refs or conflicting versions)

### Docker multi-platform image helper [ᐞ](#table-of-contents)

<a id="docker-multi-platform-image-helper"></a>

File: `scripts/docker-images.sh`

This script uses Docker on the host machine, so run it from the host (not
inside the `php` container). Use these Make targets:

```bash
make docker-image-digest          # asks IMAGE
make docker-image-verify-pins
make docker-builder-platforms
make docker-setup-qemu            # asks PLATFORMS
make docker-build-platform        # asks PLATFORM, DOCKERFILE, TAG
make docker-build-multi-platform  # asks PLATFORMS, DOCKERFILE, TAG, BUILDER
```

Missing values are asked interactively. Values can also be given as Make
variables, for example
`make docker-image-digest IMAGE=php:8.5.11-fpm-bookworm`. In a
non-interactive shell, required values without defaults must be given as
variables.

See [Docker multi-platform images](../doc/DOCKER_MULTI_PLATFORM.md) for
details.

---

[Back to previous](../README.md)
