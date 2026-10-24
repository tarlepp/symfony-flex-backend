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
      * [Skill documentation checker](#skill-documentation-checker)
      * [GitHub Actions update checker](#github-actions-update-checker)
      * [Docker multi-platform image helper](#docker-multi-platform-image-helper)

## Resources [ᐞ](#table-of-contents)

<a id="resources"></a>

* [Project stats script](project-stats.sh)
  * This script is used to generate simple project stats. It will generate
    output with some basic stats about project.
* [Skill documentation checker](check-skills.py)
  * Validates repository skill front matter, exact skills-index coverage, and
    relative Markdown links and anchors.
* [GitHub Actions update checker](check-action-updates.sh)
  * Checks pinned GitHub Actions from `.github/workflows/*.yml`, reports
    discovery issues (unpinned refs/conflicting versions), and checks for
    available updates in the current major version line.
* [Docker multi-platform image helper](docker-images.sh)
  * Checks image version updates, resolves multi-platform image digests,
    verifies pinned base images and builds images for other platforms.

### Project stats script [ᐞ](#table-of-contents)

<a id="project-stats-script"></a>

File: `scripts/project-stats.sh`

```bash
make project-stats
```

If you are already inside the container, you can still run
`bash scripts/project-stats.sh` directly.

### Skill documentation checker [ᐞ](#table-of-contents)

<a id="skill-documentation-checker"></a>

File: `scripts/check-skills.py`

```bash
python3 scripts/check-skills.py
```

This standard-library Python check is also run in the documentation CI job.

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
make docker-image-check-updates   # all external Dockerfile images
make docker-image-digest          # asks IMAGE
make docker-image-verify-pins
make docker-builder-platforms
make docker-setup-qemu            # asks PLATFORMS
make docker-build-platform        # asks PLATFORM, DOCKERFILE, TARGET, TAG
make docker-build-multi-platform  # asks PLATFORMS, DOCKERFILE, TARGET, TAG, BUILDER
```

Missing values are asked interactively. Values can also be given as Make
variables, for example
`make docker-image-digest IMAGE=php:8.5.11-fpm-bookworm`. In a
non-interactive shell, required values without defaults must be given as
variables.

Digest lookup and pin verification require at least two distinct runnable
platforms in an image index. Empty and single-platform indexes fail with an
explicit diagnostic instead of being accepted as multi-platform.

Check a single image with
`make docker-image-check-updates IMAGE=php:8.5.11-fpm-bookworm`, or run
`./scripts/docker-images.sh check-updates` to check external `FROM` and
`COPY --from` images in the root and `docker/*/Dockerfile*` Dockerfiles.
The checker uses public Docker Hub tags via `curl` and `jq`, and Docker with
buildx to resolve update digests. It reports the latest stable full semantic
version, including major upgrades, while preserving the exact variant suffix
and optional `v` prefix.
Floating, unversioned and prerelease tags are explicitly skipped. Other
registries are not supported. Exit status is `0` when there are no updates or
errors (explicit skips are allowed), `1` for available updates or errors, or
`69` for missing tools. Results are grouped under Dockerfile headings, with
shared images shown in each matching group but checked only once. An `IMAGE`
input with no matching file has its own labeled group. Each update includes a
copyable `tag@sha256:digest` pin and supported platforms.
The digest is inspected directly
and must identify an index with at least two distinct runnable platforms;
single-platform images and failed digest lookups fail the check explicitly.
No files, images or running services are changed. Review compatibility and
required platform support separately before updating pins.
Dockerfile discovery supports backslash-continued instructions, including
intervening comments and blank lines. Repeated pagination URLs fail explicitly,
including cycles spanning multiple pages.
Update labels are yellow and up-to-date labels are green; set `NO_COLOR=1`
to disable colors.

Use `./scripts/docker-images.sh check-updates` in CI to fail the step when an
update is available or a check fails. All image results are reported before
exiting. `make docker-image-check-updates` also fails in these cases, but Make
uses its own non-zero exit status rather than preserving the script's code.
Registry inspection retries transient errors up to three attempts, with 10 and
20 second delays. The first `429` error prints a notification to authenticate
with `docker login docker.io` or wait for the quota to reset, before retrying.
Persistent errors still fail. In CI,
authenticate with credentials from CI secrets before running this command.
Retry diagnostics stream immediately to stderr rather than waiting for the
grouped result. During the wait, progress is printed every five seconds with
the next attempt number and remaining delay. Rate-limited waits also remind
users to press `Ctrl+C` and run `docker login docker.io`.

The `Check Docker image versions` CI job runs alongside linting and is required
by `ci-summary`, so updates or errors block tests and Docker builds. It runs the
helper regression tests first. Configure optional `DOCKERHUB_USERNAME` and
`DOCKERHUB_TOKEN` repository secrets (a read-only Docker Hub access token) to
authenticate registry inspection. Both secrets must be present or both absent.
Fork pull requests and repositories without these secrets use anonymous requests
with an explicit notice. The job logs out even if the check fails.

Run the helper's regression tests on the host without registry access:

```bash
bash scripts/tests/docker-images-test.sh
```

The root `Dockerfile` defaults to the `production` target. Use
`TARGET=development` to build the development image instead. Dockerfiles
with an unnamed final stage do not prompt for a target and use Docker's final
stage by default. Use `TARGET` explicitly to select a named intermediate stage.

See [Docker multi-platform images](../doc/DOCKER_MULTI_PLATFORM.md) for
details.

---

[Back to previous](../README.md)
