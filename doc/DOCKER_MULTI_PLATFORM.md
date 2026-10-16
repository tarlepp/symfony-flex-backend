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
* `docker/mariadb/Dockerfile` - MariaDB image

Other images (for example `mlocati/php-extension-installer` in
`COPY --from=...`, `docker/nginx/Dockerfile_dev` and the images in
`compose.yaml`) use plain tags. Plain tags always resolve to the image index,
so they are multi-platform as long as the upstream image publishes several
platforms.

## How to update a base image pin [ᐞ](#table-of-contents)

<a id="how-to-update-a-base-image-pin"></a>

These commands are host-level Docker commands and require Docker with the
`buildx` plugin (included in Docker Desktop and current Docker Engine
packages).

### Step 1: Choose the new tag [ᐞ](#table-of-contents)

<a id="step-1-choose-the-new-tag"></a>

Pick the new tag from Docker Hub, for example `php:8.5.11-fpm-bookworm`, and
check which platforms it supports:

```bash
docker buildx imagetools inspect php:8.5.11-fpm-bookworm
```

### Step 2: Get the multi-platform index digest [ᐞ](#table-of-contents)

<a id="step-2-get-the-multi-platform-index-digest"></a>

The top-level `Digest` of the output above is the index digest. To print only
the media type and digest:

```bash
docker buildx imagetools inspect php:8.5.11-fpm-bookworm \
  --format '{{.Manifest.MediaType}} {{.Manifest.Digest}}'
```

Example output:

```text
application/vnd.oci.image.index.v1+json sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef
```

The media type **must** be an index (`...image.index...` or
`...manifest.list...`). If it is `...image.manifest...` the tag is published
for one platform only.

Alternatives that also return the index digest:

```bash
# crane (go-containerregistry)
crane digest php:8.5.11-fpm-bookworm

# skopeo
skopeo inspect --raw docker://php:8.5.11-fpm-bookworm | sha256sum
```

### Step 3: Update the Dockerfiles [ᐞ](#table-of-contents)

<a id="step-3-update-the-dockerfiles"></a>

Update the tag and digest in every file that uses the same base image. For PHP
both `Dockerfile` and `Dockerfile_dev` must use the same reference:

```dockerfile
FROM php:8.5.11-fpm-bookworm@sha256:dd198c828c224e479bdaa184cdcf04a28c73d186bf5a885493bf9cc78d7196ef
```

### Step 4: Verify the pins [ᐞ](#table-of-contents)

<a id="step-4-verify-the-pins"></a>

Run this from the project root to check that every pinned `FROM` points to a
multi-platform index and to list the platforms it supports:

```bash
grep -hoE '^FROM [^ ]+@sha256:[a-f0-9]{64}' Dockerfile Dockerfile_dev docker/*/Dockerfile* \
  | awk '{print $2}' | sort -u | while read -r ref; do
    type=$(docker buildx imagetools inspect "$ref" --format '{{.Manifest.MediaType}}')
    case "$type" in
      *index*|*manifest.list*) status="OK  " ;;
      *) status="FAIL" ;;
    esac
    platforms=$(docker buildx imagetools inspect "$ref" \
      | awk '/Platform:/ && $2 != "unknown/unknown" {print $2}' | paste -sd ' ' -)
    echo "$status $ref"
    echo "     ${platforms:-single platform image}"
  done
```

Every line should start with `OK`. A `FAIL` line means that the digest is a
single platform manifest and must be replaced with the index digest.

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
docker buildx inspect --bootstrap | grep -i platforms
```

If the needed platform is missing, install QEMU emulators (Docker Desktop
already includes them):

```bash
docker run --privileged --rm tonistiigi/binfmt --install arm64,amd64
```

Then build for a specific platform:

```bash
docker buildx build --platform linux/arm64 --file Dockerfile \
  --tag symfony-flex-backend:arm64 --load .
```

Note that emulated builds are considerably slower than native builds, because
PHP extensions are compiled during the build.

### Building and publishing a multi-platform image [ᐞ](#table-of-contents)

<a id="building-and-publishing-a-multi-platform-image"></a>

The classic Docker image store cannot hold multi-platform images, so use a
`docker-container` builder and push the result directly to a registry:

```bash
docker buildx create --name multi-platform --driver docker-container --use
docker buildx build \
  --platform linux/amd64,linux/arm64 \
  --file Dockerfile \
  --tag registry.example.com/symfony-flex-backend:latest \
  --push .
```

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

* A base image is pinned to a single platform digest. Run the verification
  command from [Step 4](#step-4-verify-the-pins) and replace the failing
  digest with the index digest.

`no match for platform in manifest`:

* The upstream image does not publish that platform. Check supported
  platforms with `docker buildx imagetools inspect <image>`.

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
