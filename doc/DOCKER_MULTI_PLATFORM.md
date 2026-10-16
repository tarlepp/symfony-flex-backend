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
    * [Make commands](#make-commands)
    * [How to update a base image pin](#how-to-update-a-base-image-pin)
      * [Step 1: Choose the new tag](#step-1-choose-the-new-tag)
      * [Step 2: Get the multi-platform index digest](#step-2-get-the-multi-platform-index-digest)
      * [Step 3: Update the Dockerfiles](#step-3-update-the-dockerfiles)
      * [Step 4: Verify the pins](#step-4-verify-the-pins)
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

* `Dockerfile` - production PHP image (`php:*-fpm-bookworm`)
* `Dockerfile_dev` - development PHP image (`php:*-fpm-bookworm`)
* `docker/nginx/Dockerfile` - production nginx image
* `docker/nginx/Dockerfile_dev` - development nginx image
* `docker/mariadb/Dockerfile` - MariaDB image

Other images (for example `mlocati/php-extension-installer` in
`COPY --from=...` and the images in `compose.yaml`) use plain tags. Plain tags
always resolve to the image index, so they are multi-platform as long as the
upstream image publishes several platforms.

## Make commands [ᐞ](#table-of-contents)

<a id="make-commands"></a>

The commands in this document are wrapped as Make targets backed by
`scripts/docker-images.sh`. They use Docker on the host machine, so run them
from the host, not inside the `php` container. They require Docker with the
`buildx` plugin (included in Docker Desktop and current Docker Engine packages)
and `jq`.

* `make docker-image-digest` - show multi-platform index digest for image
  (asks `IMAGE`)
* `make docker-image-verify-pins` - verify that PHP, nginx and MariaDB
  base images are multi-platform and that production and development
  Dockerfiles use the same pins
* `make docker-builder-platforms` - show platforms supported by the buildx
  builder
* `make docker-setup-qemu` - install QEMU emulators (asks `PLATFORMS`)
* `make docker-build-platform` - build image for a single platform (asks
  `PLATFORM`, `DOCKERFILE`, `TAG`)
* `make docker-build-multi-platform` - build and push a multi-platform image
  (asks `PLATFORMS`, `DOCKERFILE`, `TAG`, `BUILDER`)

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

Pick the new tag from Docker Hub, for example `php:8.5.11-fpm-bookworm`.

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

Dockerfile:2:FROM php:8.5.10-fpm-bookworm@sha256:...
Dockerfile_dev:1:FROM php:8.5.10-fpm-bookworm@sha256:...
```

The media type **must** be an index (`...image.index...` or
`...manifest.list...`). If the image is published for one platform only, the
command prints a warning and exits with code `1`.

Manual equivalent:

```bash
docker buildx imagetools inspect php:8.5.11-fpm-bookworm \
  --format '{{.Manifest.MediaType}} {{.Manifest.Digest}}'
```

### Step 3: Update the Dockerfiles [ᐞ](#table-of-contents)

<a id="step-3-update-the-dockerfiles"></a>

Replace the `FROM` lines listed by the previous command with the printed pin
reference. Production and development Dockerfiles of the same service must use
the same reference (`Dockerfile` and `Dockerfile_dev` for PHP,
`docker/nginx/Dockerfile` and `docker/nginx/Dockerfile_dev` for nginx):

```dockerfile
FROM php:8.5.11-fpm-bookworm@sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef
```

### Step 4: Verify the pins [ᐞ](#table-of-contents)

<a id="step-4-verify-the-pins"></a>

```bash
make docker-image-verify-pins
```

This runs two checks and shows the result per file and per `FROM` line,
followed by a summary.

First, it checks that every `FROM` line in these files uses a multi-platform
base image:

* `Dockerfile` and `Dockerfile_dev` - PHP images
* `docker/nginx/Dockerfile` and `docker/nginx/Dockerfile_dev` - nginx images
* `docker/mariadb/Dockerfile` - MariaDB image

```text
[OK] Verifying base images of 5 Dockerfiles

Dockerfile
  line 2: php:8.5.11-fpm-bookworm@sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef
  OK    Multi-platform: linux/amd64 linux/arm/v7 linux/arm64/v8 linux/386 linux/ppc64le

...

docker/nginx/Dockerfile_dev
  line 1: nginx:mainline-alpine-slim@sha256:45b82ed5f285b90d63df07ba70430fdd8f25624b416617d9e6dc93412b2006dc
  OK    Multi-platform: linux/amd64 linux/arm/v6 linux/arm/v7 linux/arm64/v8 linux/386 linux/ppc64le linux/riscv64 linux/s390x

docker/mariadb/Dockerfile
  line 1: mariadb:10.7.1@sha256:5a8009369ee57c85b6f4a08406147bd9c505cde6b8250d16a27d2a5febfdead7
  OK    Multi-platform: linux/amd64 linux/arm64/v8 linux/ppc64le linux/s390x

[OK] Comparing base images of production and development Dockerfiles

Dockerfile <-> Dockerfile_dev
  OK    Same digest: sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef (php:8.5.11-fpm-bookworm)

docker/nginx/Dockerfile <-> docker/nginx/Dockerfile_dev
  OK    Same digest: sha256:45b82ed5f285b90d63df07ba70430fdd8f25624b416617d9e6dc93412b2006dc (nginx:mainline-alpine-slim)

Summary
  Multi-platform base images:
    Dockerfile                                               OK
    Dockerfile_dev                                           OK
    docker/nginx/Dockerfile                                  OK
    docker/nginx/Dockerfile_dev                              OK
    docker/mariadb/Dockerfile                                OK
  Production and development use same base images:
    Dockerfile <-> Dockerfile_dev                            OK
    docker/nginx/Dockerfile <-> docker/nginx/Dockerfile_dev  OK

[OK] All Dockerfiles use multi-platform base images and production/development pins match
```

Statuses for each `FROM` line:

* `OK` - pinned to a multi-platform index digest; supported platforms are
  listed.
* `WARN` - not pinned with a digest. Tags resolve to the index, so this is
  multi-platform if the upstream image is, but it is not reproducible.
* `FAIL` - pinned to a single platform manifest digest, or the image could not
  be inspected. Replace the digest with the one from
  `make docker-image-digest`.

References to earlier build stages (`FROM base AS final`) are skipped.

Second, it compares each production Dockerfile with its development
counterpart (`<file>` and `<file>_dev`). Both files must use exactly the same
base image references, including tag and digest, so that development and
production run on the same base image. If they differ, the command shows the
references that exist only in one of the files:

```text
Dockerfile <-> Dockerfile_dev
  FAIL  Base images differ, use the same pin reference in both files
        Dockerfile:     php:8.5.11-fpm-bookworm@sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef
        Dockerfile_dev: php:8.5.10-fpm-bookworm@sha256:f96e7316935fc38a4a3acb9aae77b647df261e82997af93128719005393f96b1
```

Warnings do not fail the command; it exits with code `1` if any check fails.
Use `make docker-image-digest` to check any other image.

## Building multi-platform images [ᐞ](#table-of-contents)

<a id="building-multi-platform-images"></a>

### Local development [ᐞ](#table-of-contents)

<a id="local-development"></a>

Nothing special is needed. `make start` / `make daemon` build the images with
Docker Compose, which builds for the platform of the host machine. With index
digests this means a native `linux/arm64` image on Apple Silicon and a native
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
(default `Dockerfile`) and the image tag (default based on platform, for
example `symfony-flex-backend:arm64`), builds the image and loads it into the
local Docker image store. The build context is the directory of the selected
Dockerfile, and `HOST_UID` / `HOST_GID` build arguments are passed
automatically when the Dockerfile needs them (`Dockerfile_dev`).

Non-interactive example:

```bash
make docker-build-platform PLATFORM=linux/arm64 DOCKERFILE=Dockerfile TAG=symfony-flex-backend:arm64
```

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
Dockerfile, the image tag including registry (for example
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
