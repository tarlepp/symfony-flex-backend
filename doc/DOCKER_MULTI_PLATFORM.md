# What is this?

<a id="what-is-this"></a>

This document explains how Docker base images are pinned in this project so
that the same `Dockerfile` works on multiple CPU architectures (for example
`linux/amd64` / x86-64 and `linux/arm64` / Apple Silicon, AWS Graviton,
Raspberry Pi 64-bit).

It also describes how to update those pins and how to build multi-platform
images.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Background](#background)
      * [Tags, image indexes and platform manifests](#tags-image-indexes-and-platform-manifests)
      * [Why the digest matters](#why-the-digest-matters)
    * [Pinned base images](#pinned-base-images)
    * [PHP build targets](#php-build-targets)
    * [Running production locally](#running-production-locally)
    * [Make commands](#make-commands)
    * [How to update a base image pin](#how-to-update-a-base-image-pin)
      * [Step 1: Choose the new tag](#step-1-choose-the-new-tag)
      * [Step 2: Get the multi-platform index digest](#step-2-get-the-multi-platform-index-digest)
      * [Step 3: Update the Dockerfiles](#step-3-update-the-dockerfiles)
      * [Step 4: Verify the pins](#step-4-verify-the-pins)
      * [Continuous integration](#continuous-integration)
    * [Building multi-platform images](#building-multi-platform-images)
      * [Local development](#local-development)
      * [Building for another platform](#building-for-another-platform)
      * [Building and publishing a multi-platform image](#building-and-publishing-a-multi-platform-image)
    * [Things to keep platform independent](#things-to-keep-platform-independent)
    * [Troubleshooting](#troubleshooting)
    * [Useful resources](#useful-resources)

## Background [ᐞ](#table-of-contents)

<a id="background"></a>

### Tags, image indexes and platform manifests [ᐞ](#table-of-contents)

<a id="tags-image-indexes-and-platform-manifests"></a>

A multi-platform image reference such as `php:8.5.11-fpm-bookworm` points to an
**image index** (OCI `application/vnd.oci.image.index.v1+json`, or the older
Docker `application/vnd.docker.distribution.manifest.list.v2+json`). The index
lists one **image manifest** per platform:

```text
php:8.5.11-fpm-bookworm
└── image index        sha256:dd198c82...   <-- pin this one
    ├── linux/amd64    sha256:9583ff1e...
    ├── linux/arm64/v8 sha256:58405d3f...
    ├── linux/arm/v7   sha256:29dc32d8...
    ├── linux/386      sha256:4aa1fea7...
    └── linux/ppc64le  sha256:00ce0b35...
```

When Docker pulls an index, it picks the manifest that matches the platform of
the build (or the `--platform` that was requested).

### Why the digest matters [ᐞ](#table-of-contents)

<a id="why-the-digest-matters"></a>

The project pins base images with `tag@sha256:digest` for reproducible and
supply-chain safe builds. When both tag and digest are present, Docker uses
**only the digest**.

* If the digest is the **image index** digest, every supported platform works
  and Docker selects the correct architecture automatically.
* If the digest is a **single platform manifest** digest (for example the
  `linux/amd64` one), every build uses that architecture. On an ARM machine
  this either fails or runs slowly under emulation.

A common way to end up with a single platform digest is copying the digest
shown for one `OS/ARCH` row on the Docker Hub tag page, or reading it from an
already pulled local image. Always use the index digest.

## Pinned base images [ᐞ](#table-of-contents)

<a id="pinned-base-images"></a>

These files contain pinned base images:

* `Dockerfile` - shared PHP base for development and production targets
  (`php:*-fpm-bookworm`)
* `docker/nginx/Dockerfile` - shared production and development nginx image
* `docker/mariadb/Dockerfile` - MariaDB image

The shared nginx image includes the production `nginx.conf` by default.
For development, `compose.yaml` mounts `nginx-dev.conf` over the image's default
configuration and mounts the local TLS certificates. No separate nginx
Dockerfile or build arguments are needed.

Other images (for example `mlocati/php-extension-installer` in
`COPY --from=...` and the images in `compose.yaml`) use plain tags. Plain tags
always resolve to the image index, so they are multi-platform as long as the
upstream image publishes several platforms.

## PHP build targets [ᐞ](#table-of-contents)

<a id="php-build-targets"></a>

The root `Dockerfile` has a shared `base` stage and two runnable targets:

* `development` includes Xdebug, the host-mapped `dev` user, Bash/Fish
  completions, editors, Python helpers and Node.js via nvm. It uses
  `php-dev.ini`, `www-dev.conf` and `docker-entrypoint-dev.sh`, which installs
  development dependencies when the container starts.
* `production` uses `php.ini`, `www.conf` and `docker-entrypoint.sh`, sets
  `APP_ENV=prod` / `APP_DEBUG=0`, installs optimized Composer dependencies
  without dev packages and audits them during the build. Development-only
  tools and Xdebug are not inherited.

Production is the last stage and therefore the default for `docker build .`.
Docker Compose selects `development` explicitly, including when used by Dev
Containers. Both targets inherit the same PHP extensions, Composer version,
security updates, working directory and PHP-FPM port from `base`.

```bash
docker build --target production -t symfony-flex-backend:prod .
docker build --target development \
  --build-arg HOST_UID="$(id -u)" --build-arg HOST_GID="$(id -g)" \
  -t symfony-flex-backend:dev .
```

`HOST_UID` and `HOST_GID` default to `1000` and only configure the development
user. Existing checkouts should rebuild the PHP image after switching to the
unified Dockerfile (`make daemon-build` or `make start-build`).

## Running production locally [ᐞ](#table-of-contents)

<a id="running-production-locally"></a>

Use Docker Compose **2.24.4 or newer** (the override uses `!override` to replace
development mounts and ports):

```bash
make stop
make daemon-prod
```

`make daemon-prod` builds the production image and starts the existing local
stack in the background, waiting for it to become ready. `make start-prod`
builds and starts the same stack in the foreground with logs attached.
Stop the existing stack first; development and local production cannot run
together because they share container names, ports and the MariaDB volume.

Open **<http://localhost:8001>**. This mode uses the production nginx
configuration: local HTTPS on port `8000` and the development nginx status
route are not enabled. Adminer (`8200`), Dozzle (`8100`) and MariaDB (`33060`)
remain available as local development conveniences. This is not a hardened
deployment configuration.

When Docker runs inside a VM using NAT, forward host TCP port `8001` to guest
TCP port `8001`, then use <http://localhost:8001> on the host.

The override `compose.prod.yaml` selects the `production` PHP target, forces
`APP_ENV=prod` / `APP_DEBUG=0`, removes development source/configuration
mounts, and mounts `secrets/application.json` read-only. Configure that file
for the local MariaDB service (`mariadb:3306`) before starting. The existing
production entrypoint generates JWT keys and runs migrations against the
**same database used by development**; back up important data first.
Generated keys live in the container, not the host checkout, so recreating
the container invalidates previously issued tokens.

PHP replaces the contents of its named application volume for nginx on each
startup, including removing obsolete files and dotfiles from earlier builds.
Database readiness gates PHP startup, and nginx waits until PHP-FPM is accepting
connections after initialization. No host source tree or development PHP
configuration is mounted. Code changes require another production build.
The production PHP image uses a separate tag so switching modes cannot
accidentally reuse it as the development image.

The production entrypoint grants PHP-FPM ownership of `/app/var` rather
than changing permissions on the entire application, preserving the
read-only configuration mount.

Use `make logs` and `make stop` in either mode. For direct Compose commands
in production mode, include both files:

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console about
```

Switch back to development without deleting database volumes:

```bash
make stop
make daemon
```

## Make commands [ᐞ](#table-of-contents)

<a id="make-commands"></a>

The commands in this document are wrapped as Make targets backed by
`scripts/docker-images.sh`. They use Docker on the host machine, so run them
from the host, not inside the `php` container. They require Docker with the
`buildx` plugin (included in Docker Desktop and current Docker Engine packages)
and `jq`. The read-only update checker additionally requires `curl`.

* `make docker-image-digest` - show multi-platform index digest for image
  (asks `IMAGE`)
* `make docker-image-check-updates` - report newer stable Docker Hub versions
  for external Dockerfile images, including `COPY --from` tools; optionally
  set `IMAGE` to check a single image
* `make docker-image-verify-pins` - verify that PHP, nginx and MariaDB
  base images are multi-platform
* `make docker-builder-platforms` - show platforms supported by the buildx
  builder
* `make docker-setup-qemu` - install QEMU emulators (asks `PLATFORMS`)
* `make docker-build-platform` - build image for a single platform (asks
  `PLATFORM`, `DOCKERFILE`, `TARGET`, `TAG`)
* `make docker-build-multi-platform` - build and push a multi-platform image
  (asks `PLATFORMS`, `DOCKERFILE`, `TARGET`, `TAG`, `BUILDER`)

Missing values are asked interactively, and the suggested default is shown in
brackets. Values can also be given as Make variables to skip the questions:

```bash
make docker-image-digest IMAGE=php:8.5.11-fpm-bookworm
```

In a non-interactive shell (for example CI), required values without defaults
must be given as variables. Commands that push images or run privileged
containers always ask for confirmation.

## How to update a base image pin [ᐞ](#table-of-contents)

<a id="how-to-update-a-base-image-pin"></a>

### Step 1: Choose the new tag [ᐞ](#table-of-contents)

<a id="step-1-choose-the-new-tag"></a>

Check available versions from Docker Hub:

```bash
make docker-image-check-updates
make docker-image-check-updates IMAGE=php:8.5.11-fpm-bookworm
```

The checker requires `curl`, `jq` and Docker with buildx. It checks full stable
semantic-version tags, including major upgrades, preserving the current
variant (for example `-fpm-bookworm`). It follows all tag pages and compares
versions numerically, not by publication date. Floating and prerelease tags
are skipped with a diagnostic; registries other than Docker Hub are unsupported.
Results are grouped by source Dockerfile. Shared images appear in each relevant
group but are checked only once. Each update includes a copyable
`tag@sha256:digest` reference and its supported platforms, verified by inspecting
the resolved digest as an index with at least two distinct runnable platforms.
Failed digest lookups or single-platform updates fail explicitly.
It reports updates without modifying files or checking current-tag digest
changes or upgrade compatibility. It exits `0` only when there are no updates
or errors (explicit skips are allowed). Available updates or lookup errors exit
`1`; missing tools exit `69`. All images are checked before reporting failure,
so the command can be used as a CI gate. Make also fails, but does not preserve
the script's exact non-zero exit code.

Review compatibility before choosing the new tag, especially for major upgrades.
Use the following steps to inspect and update its digest.

### Step 2: Get the multi-platform index digest [ᐞ](#table-of-contents)

<a id="step-2-get-the-multi-platform-index-digest"></a>

```bash
make docker-image-digest
```

The command asks the image name and prints the media type, index digest,
supported platforms, the `FROM` line to use, and the current `FROM` lines that
use the same image:

```text
Image name and tag (e.g. php:8.5.11-fpm-bookworm): php:8.5.11-fpm-bookworm
[OK] Inspecting php:8.5.11-fpm-bookworm

Media type: application/vnd.oci.image.index.v1+json
Digest:     sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef
Platforms:  linux/amd64 linux/arm/v7 linux/arm64/v8 linux/386 linux/ppc64le

Pin reference:

    FROM php:8.5.11-fpm-bookworm@sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef

Dockerfile:2:FROM php:8.5.10-fpm-bookworm@sha256:... AS base
```

The media type **must** be an index (`...image.index...` or
`...manifest.list...`) containing at least two distinct runnable platforms.
Attestation manifests, unknown or missing platforms, and duplicate platform
entries do not count. A single-platform manifest, empty index, or index with
only one runnable platform prints a warning and exits with code `1`, without
printing a pin reference.

An upstream tag can temporarily resolve to an empty index while images are
being published. Wait for the platform images or choose another available tag.
Resolve the tag again before pinning: an empty index digest stays empty even
after the tag points to a populated index.

Manual equivalent:

```bash
docker buildx imagetools inspect php:8.5.11-fpm-bookworm \
  --format '{{.Manifest.MediaType}} {{.Manifest.Digest}}'
```

### Step 3: Update the Dockerfiles [ᐞ](#table-of-contents)

<a id="step-3-update-the-dockerfiles"></a>

Replace the `FROM` lines listed by the previous command with the printed pin
reference. PHP has a single pin in the shared `base` stage of `Dockerfile`,
so both targets automatically use the same reference. For nginx, update
`docker/nginx/Dockerfile`:

```dockerfile
FROM php:8.5.11-fpm-bookworm@sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef AS base
```

### Step 4: Verify the pins [ᐞ](#table-of-contents)

<a id="step-4-verify-the-pins"></a>

```bash
make docker-image-verify-pins
```

This checks registry base images and shows the result per file and per `FROM` line,
followed by a summary.

It checks that every external `FROM` line in these files uses a multi-platform
base image:

* `Dockerfile` - shared PHP base
* `docker/nginx/Dockerfile` - shared nginx image
* `docker/mariadb/Dockerfile` - MariaDB image

```text
[OK] Verifying base images of 3 Dockerfiles

Dockerfile
  line 2: php:8.5.11-fpm-bookworm@sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef
  OK    Multi-platform: linux/amd64 linux/arm/v7 linux/arm64/v8 linux/386 linux/ppc64le

...

docker/nginx/Dockerfile
  line 1: nginx:mainline-alpine-slim@sha256:45b82ed5f285b90d63df07ba70430fdd8f25624b416617d9e6dc93412b2006dc
  OK    Multi-platform: linux/amd64 linux/arm/v6 linux/arm/v7 linux/arm64/v8 linux/386 linux/ppc64le linux/riscv64 linux/s390x

docker/mariadb/Dockerfile
  line 1: mariadb:10.7.1@sha256:5a8009369ee57c85b6f4a08406147bd9c505cde6b8250d16a27d2a5febfdead7
  OK    Multi-platform: linux/amd64 linux/arm64/v8 linux/ppc64le linux/s390x

Summary
  Multi-platform base images:
    Dockerfile                                               OK
    docker/nginx/Dockerfile                                  OK
    docker/mariadb/Dockerfile                                OK

[OK] All Dockerfiles use multi-platform base images
```

Statuses for each `FROM` line:

* `OK` - pinned to a multi-platform index digest; supported platforms are
  listed.
* `WARN` - not pinned with a digest. Tags resolve to the index, so this is
  multi-platform if the upstream image is, but it is not reproducible.
* `FAIL` - pinned to a single platform manifest, an index with fewer than two
  distinct runnable platforms, or the image could not be inspected. Choose
  an available multi-platform image using `make docker-image-digest`.

References to earlier build stages (`FROM base AS final`) are skipped.

No production/development file comparison is needed: both PHP targets inherit
the same `base` stage, and nginx also uses one shared Dockerfile.

Warnings do not fail the command; it exits with code `1` if any check fails.
Use `make docker-image-digest` to check any other image.

### Continuous integration [ᐞ](#table-of-contents)

<a id="continuous-integration"></a>

The `Check Docker image versions` job (`docker-image-check-updates` in
`.github/workflows/main.yml`) starts alongside linting on the workflow's push,
pull request, scheduled and manual runs. It is one of the two periodic jobs
that run on the daily schedule, together with `security-check`. It runs `make test-docker-images`
inside the PHP development container and `make docker-image-check-updates`
on the Docker host. The `ci-summary` prerequisite gate
requires this job to succeed, so available updates or lookup errors block both
tests and Docker builds. On scheduled runs the gate additionally requires all
other prerequisites to be skipped; on every other event they must succeed.
Explicitly skipped tags remain non-failing.

For authenticated registry requests, configure both `DOCKERHUB_USERNAME` and
`DOCKERHUB_TOKEN` repository secrets; use a read-only Docker Hub access token.
Without either secret (including fork pull requests), the job reports that it
is using anonymous requests. Configuring only one secret fails explicitly.
Credentials are passed through environment variables and `--password-stdin`;
the job logs out even if the check fails. No secrets are required for the core
check to run, but anonymous rate limits can still fail it.

Pin verification also runs in GitHub Actions in the
`Verify Docker base image pins (multi-platform)` job
(`docker-image-verify-pins` in `.github/workflows/main.yml`). The job has no
dependencies, so it starts immediately in parallel with the first lint and
security jobs. Both the test job and the `Build application Docker image` job
depend on it, so a pull request that pins a single platform digest fails fast,
before tests are run or the image is built.

CI builds and scans both PHP targets on pull requests and `master` pushes.
The daily vulnerability scan (`vulnerability-scan.yml`) builds and scans both
the `development` and `production` targets with the same Trivy threshold.

The job runs `make docker-image-verify-pins` directly on the runner, which
already has Docker with `buildx` and `jq` installed. It uses read-only
permissions and a shallow checkout without persisted credentials.

When running in GitHub Actions, the command also:

* Adds error and warning annotations to the failing `FROM` lines, so problems
  are shown directly in the pull request "Files changed" view.
* Writes a result summary with per-file and per-image tables to the job
  summary page.

Transient registry errors (for example Docker Hub `429 Too Many Requests`,
timeouts and server errors) are retried twice with a short delay, both in CI
and locally. This includes update digest lookups and raw manifest inspection,
with delays of 10 and 20 seconds. Persistent throttling still fails the check;
retries do not bypass Docker Hub quotas. Authenticate with `docker login docker.io`
or wait for the limit to reset before retrying.
Retry progress streams to stderr every five seconds, showing the next attempt
number and remaining delay. For rate limits, each countdown includes a reminder
to stop with `Ctrl+C` and authenticate before rerunning the command.

## Building multi-platform images [ᐞ](#table-of-contents)

<a id="building-multi-platform-images"></a>

### Local development [ᐞ](#table-of-contents)

<a id="local-development"></a>

Nothing special is needed. `make start` / `make daemon` build the images with
Docker Compose, which selects the `development` target and builds for the
platform of the host machine. With index digests this means a native
`linux/arm64` image on Apple Silicon and a native
`linux/amd64` image on x86-64 machines.

### Building for another platform [ᐞ](#table-of-contents)

<a id="building-for-another-platform"></a>

To build for an architecture that differs from the host, the builder needs
either a native node for that architecture or QEMU emulation. Check which
platforms your builder supports:

```bash
make docker-builder-platforms
```

If the needed platform is missing, install QEMU emulators (Docker Desktop
already includes them). The command asks which emulators to install (default
`arm64,amd64`) and asks for confirmation, because it runs the privileged
`tonistiigi/binfmt` container:

```bash
make docker-setup-qemu
```

Then build for a specific platform:

```bash
make docker-build-platform
```

The command asks the target platform (default `linux/arm64`), the Dockerfile
(default `Dockerfile`), build target (default `production`) and image tag
(default based on platform, for example `symfony-flex-backend:arm64`),
builds the image and loads it into the local Docker image store. The build
context is the directory of the selected
Dockerfile, and `HOST_UID` / `HOST_GID` build arguments are passed
automatically when the Dockerfile declares them.

Non-interactive example:

```bash
make docker-build-platform PLATFORM=linux/arm64 DOCKERFILE=Dockerfile TARGET=production TAG=symfony-flex-backend:arm64
```

Use `TARGET=development` to build the development image instead. For other
multi-stage Dockerfiles the target defaults to the final stage. If that stage
is unnamed, the helper does not prompt for a target and lets Docker select
the final stage. Named intermediate stages can still be selected explicitly
with `TARGET`. Single-stage Dockerfiles without a named stage do not prompt
for a target.

Note that emulated builds are considerably slower than native builds, because
PHP extensions are compiled during the build.

### Building and publishing a multi-platform image [ᐞ](#table-of-contents)

<a id="building-and-publishing-a-multi-platform-image"></a>

The classic Docker image store cannot hold multi-platform images, so the image
is built with a `docker-container` builder and pushed directly to a registry:

```bash
make docker-build-multi-platform
```

The command asks the target platforms (default `linux/amd64,linux/arm64`), the
Dockerfile, build target, image tag including registry (for example
`registry.example.com/symfony-flex-backend:latest`) and the builder name
(default `multi-platform`). After confirmation, it creates the builder if it
does not exist yet and builds and pushes the image. Your default builder is not
changed. Log in to the registry first with `docker login`.

Alternatively, enable the
[containerd image store](https://docs.docker.com/engine/storage/containerd/)
to be able to `--load` multi-platform images locally.

In GitHub Actions, the same can be done with the `docker/setup-qemu-action`,
`docker/setup-buildx-action` and `docker/build-push-action` actions (remember to
pin them to commit SHAs, see [GitHub Actions update guide](ACTIONS_UPDATE.md)).

## Things to keep platform independent [ᐞ](#table-of-contents)

<a id="things-to-keep-platform-independent"></a>

When changing the Dockerfiles, keep them architecture neutral:

* Pin base images with the image index digest, never a platform digest.
* Do not hardcode architecture specific download URLs (`amd64`, `x86_64`,
  `linux-x64`, ...). If a binary must be downloaded, use the predefined
  `TARGETARCH` / `TARGETPLATFORM` build arguments to choose the right one.
* Prefer distribution packages (`apt-get`, `apk`), `install-php-extensions`
  and installers like `nvm`, which detect the architecture automatically.
* Do not add `platform:` to services in `compose.yaml` unless a specific
  architecture is really required.

## Troubleshooting [ᐞ](#table-of-contents)

<a id="troubleshooting"></a>

`The requested image's platform (linux/amd64) does not match the detected host
platform (linux/arm64/v8)` or `exec format error`:

* A base image is pinned to a single platform digest. Run
  `make docker-image-verify-pins` and replace the failing digest with the
  index digest from `make docker-image-digest`.

`no match for platform in manifest`:

* The upstream image does not publish that platform. Check supported
  platforms with `make docker-image-digest`.

`toomanyrequests: You have reached your unauthenticated pull rate limit`:

* Docker Hub limits anonymous registry requests. Log in with `docker login`
  or wait for the limit to reset.
* For CI, authenticate to Docker Hub before running the checker, using credentials
  stored in CI secrets. The script uses Docker's existing credentials; it never
  logs in automatically. Authentication can help with anonymous quotas but does
  not guarantee protection from all rate limits.

Build works on x86-64 but fails on ARM in a `RUN` step:

* Some package, repository or downloaded binary is not available for that
  architecture. Check the failing step for architecture specific assumptions.

## Useful resources [ᐞ](#table-of-contents)

<a id="useful-resources"></a>

* [Docker multi-platform builds](https://docs.docker.com/build/building/multi-platform/)
* [docker buildx imagetools](https://docs.docker.com/reference/cli/docker/buildx/imagetools/)
* [OCI image index specification](https://github.com/opencontainers/image-spec/blob/main/image-index.md)
* [Official PHP image](https://hub.docker.com/_/php)

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
