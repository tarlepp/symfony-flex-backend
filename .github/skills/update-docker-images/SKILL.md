---
name: update-docker-images
description: Update Docker image tags and multi-platform digest pins in Dockerfiles, Compose files, and CI using this repository's image inspection and validation workflow.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when updating Docker base images, images copied from external
build stages, Compose service images, or CI container images. For Composer
updates, use [update-dependencies](../update-dependencies/SKILL.md); for action
pins, use [update-github-actions](../update-github-actions/SKILL.md). For changes
to the supported PHP runtime baseline, use [update-php-runtime](../update-php-runtime/SKILL.md).
For build behavior, Compose services, entrypoints, runtime configuration, or Dev
Container setup, also use [update-docker-environment](../update-docker-environment/SKILL.md).

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and [AI rules](../../../doc/AI_RULES.md),
and the image-pinning procedure in [DOCKER_MULTI_PLATFORM.md](../../../doc/DOCKER_MULTI_PLATFORM.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Identify the requested images and acceptable update range. Inspect
   `Dockerfile`, `docker/*/Dockerfile*`, `compose*.yaml`, Dev Container
   configuration, and workflow files for relevant references, including external
   `COPY --from` images, the Dockerfile syntax frontend, Dev Container `image`
   references and features, and GitHub Actions job `container:` and `services:`
   images. For parameterized Dockerfile `FROM` references, trace the relevant
   `ARG` defaults and build arguments to identify the resolved image. Action
   references such as `uses: owner/action@...` are handled by
   [update-github-actions](../update-github-actions/SKILL.md), not as container
   images. Distinguish registry images from local build-stage names and locally
   built application images. Ask before a broad update if scope or version range
   is unclear; do not update unrelated images.
2. Inspect the current tags and pins and review candidate tags, upstream release
   notes, supported platforms, and upgrade instructions. Preserve the existing
   runtime variant and distribution unless a change is explicitly approved.

   For explicit distribution changes, review package sources, security-scanner
   suites, runtime configuration, docs, tests, and image-update automation.

   Check PHP compatibility against `composer.json` and `composer.lock`,
   extension installation and OS packages in `Dockerfile`, and affected service
   configuration. Ask before crossing major versions or making database
   compatibility or persistent-volume changes.
3. Run Docker inspection and build commands from the host, not inside the PHP
   container. These helpers require Docker with buildx, `jq`, and registry
   access. Resolve each candidate using
   `make docker-image-digest IMAGE=<image>:<tag>`, supplying the image
   explicitly to avoid interactive prompts. Use the multi-platform image index
   digest, never a single-platform manifest digest or a digest from a locally
   pulled image. Confirm the index includes the required platforms, including
   `linux/amd64` and `linux/arm64` where supported by the current image.
4. Update the tag and digest together for pinned references. A tag-only edit
   does not update an image when its old digest remains. Preserve existing
   pinning conventions for plain-tag references; do not introduce a new pinning
   policy or replace versioned tags with floating tags without approval. Update
   all references to the same image within the requested scope, preserving stage
   aliases, build targets, and build arguments. The root PHP `base` stage is
   shared by `development` and `production`; nginx also has one shared
   Dockerfile. The maintenance workflow synchronizes Doctrine and the CI MariaDB
   pin even when the image tag is unchanged. For unchanged PHP tags, it verifies
   Composer baselines and lockfiles and fails on drift for review rather than
   silently changing the supported PHP requirement.
5. Run `make docker-image-verify-pins`. It covers external `FROM` and
   `COPY --from` references in the root PHP, nginx, and MariaDB Dockerfiles, not
   every image reference in the repository. Inspect changed Compose, CI, or
   frontend images separately with `make docker-image-digest IMAGE=...`. Review
   warnings as well as failures: unpinned references can warn without failing
   verification. Confirm each updated tag resolves to its recorded digest;
   multi-platform verification alone does not prove tag/digest parity. Run this
   live check for changes to Dockerfile stages or pin-maintenance workflows even
   when the diff does not change a tag or digest. Registry tags can move without
   a version change; offline tests and cached builds do not detect that drift.
   If parity fails, resolve the exact tag again rather than copying a digest
   from an older CI log. Confirm that the current index retains the old runnable
   platforms and rerun the blocking check after any authorized refresh. The
   update checker warns and skips a newer index with no runnable platforms;
   digest resolution, pin updates, and pin verification remain strict.
6. Validate affected Compose configuration with `docker compose config --quiet`
   and include `compose.prod.yaml` when production configuration is affected.
   Build affected images; for PHP base or copied-tool updates, build both
   `development` and `production` targets using the documented commands. Use
   `make docker-build-platform` with explicit `PLATFORM`, `DOCKERFILE`,
   `TARGET`, and `TAG` values when a cross-platform build is needed. Run
   relevant application checks inside the PHP development container when runtime
   behavior could change. If an updated image contributes to the PHP
   `development` target, including a base or copied-tool image, verify
   interactive shells and affected noninteractive tooling in the freshly built
   image using [update-docker-environment](../update-docker-environment/SKILL.md#validation).
   Do this for image-only updates too, not just distribution or interpreter
   upgrades. A build or an existing container alone is not shell verification.
7. Do not recreate running services, migrate databases, remove volumes, push
   images, or install privileged QEMU emulators without explicit approval.
   Registry inspection and builds do not require these operations. Update
   related documentation only when the update changes supported platforms,
   configuration, or contributor commands; avoid duplicating changing versions
   in long-form guidance.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Complete the pin verification, tag/digest parity checks, Compose validation,
  and affected image builds described in the workflow.
* For updates affecting the PHP development image, verify Bash and Fish shell
  behavior and affected noninteractive callers as described above. Report checks
  that could not be performed, including actual Make entry points if only
  equivalent isolated shell paths were exercised.
* Run relevant application checks inside the PHP development container when
  runtime behavior could change, following shared guidance. Report any
  unavailable platform builds or other validation blockers explicitly.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
