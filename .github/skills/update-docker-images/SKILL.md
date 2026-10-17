---
name: update-docker-images
description: Update Docker image tags and multi-platform digest pins in Dockerfiles, Compose files, and CI using this repository's image inspection and validation workflow.
---

# Update Docker images

Use this skill when updating Docker base images, images copied from external
build stages, Compose service images, or CI container images. For Composer or
GitHub Actions dependency updates, use
`.github/skills/update-dependencies/SKILL.md` instead.

Follow the shared skill guidance in `.github/skills/shared-guidance.md` and
the image-pinning procedure in `doc/DOCKER_MULTI_PLATFORM.md`.

## Workflow

1. Identify the requested images and acceptable update range. Inspect
   `Dockerfile`, `docker/*/Dockerfile*`, `compose*.yaml`, Dev Container
   configuration, and workflow files for relevant references, including
   external `COPY --from` images and the Dockerfile syntax frontend.
   Distinguish registry images from local build-stage names and locally built
   application images. Ask before a broad update if scope or version range is
   unclear; do not update unrelated images.
2. Inspect the current tags and pins and review candidate tags, upstream
   release notes, supported platforms, and upgrade instructions. Preserve the
   existing runtime variant and distribution unless a change is explicitly
   approved. Check PHP compatibility against `composer.json` and
   `composer.lock`, extension installation and OS packages in `Dockerfile`,
   and affected service configuration. Ask before crossing major versions or
   making database compatibility or persistent-volume changes.
3. Run Docker inspection and build commands from the host, not inside the
   PHP container. These helpers require Docker with buildx, `jq`, and registry
   access. Resolve each candidate using
   `make docker-image-digest IMAGE=<image>:<tag>`, supplying the image explicitly
   to avoid interactive prompts. Use the multi-platform image index digest,
   never a single-platform manifest digest or a digest from a locally pulled
   image. Confirm the index includes the required platforms, including
   `linux/amd64` and `linux/arm64` where supported by the current image.
4. Update the tag and digest together for pinned references. A tag-only edit
   does not update an image when its old digest remains. Preserve existing
   pinning conventions for plain-tag references; do not introduce a new
   pinning policy or replace versioned tags with floating tags without
   approval. Update all references to the same image within the requested
   scope, preserving stage aliases, build targets, and build arguments.
   The root PHP `base` stage is shared by `development` and `production`;
   nginx also has one shared Dockerfile.
5. Run `make docker-image-verify-pins`. It covers external `FROM` references
   in the root PHP, nginx, and MariaDB Dockerfiles, not every image reference
   in the repository. Inspect changed external `COPY --from`, Compose, CI,
   or frontend images separately with `make docker-image-digest IMAGE=...`.
   Review warnings as well as failures: unpinned references can warn without
   failing verification. Confirm each updated tag resolves to its recorded
   digest; multi-platform verification alone does not prove tag/digest parity.
6. Validate affected Compose configuration with `docker compose config --quiet`
   and include `compose.prod.yaml` when production configuration is affected.
   Build affected images; for PHP base or copied-tool updates, build both
   `development` and `production` targets using the documented commands.
   Use `make docker-build-platform` with explicit `PLATFORM`, `DOCKERFILE`,
   `TARGET`, and `TAG` values when a cross-platform build is needed. Run
   relevant application checks inside the PHP development container when
   runtime behavior could change.
7. Do not recreate running services, migrate databases, remove volumes, push
   images, or install privileged QEMU emulators without explicit approval.
   Registry inspection and builds do not require these operations. Update
   related documentation only when the update changes supported platforms,
   configuration, or contributor commands; avoid duplicating changing
   versions in long-form guidance.
