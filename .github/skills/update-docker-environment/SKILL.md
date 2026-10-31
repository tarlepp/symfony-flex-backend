---
name: update-docker-environment
description: Change Docker build stages, Compose services, entrypoints, PHP/nginx runtime configuration, and Dev Container setup while preserving development, local production, CI, and persistent-data boundaries.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when changing container behavior rather than only image versions:
Dockerfile stages, installed tools/extensions, Compose services and overrides,
entrypoints, mounts, runtime configuration, or IDE Dev Container setup.
For image tags and digests alone, use
[update-docker-images](../update-docker-images/SKILL.md). For a PHP baseline
change, use [update-php-runtime](../update-php-runtime/SKILL.md).

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md). Use
[maintain-development-tooling](../maintain-development-tooling/SKILL.md) for
Make/script contracts and
[update-github-workflows](../update-github-workflows/SKILL.md) for CI consumers.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Environment map](#environment-map)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Environment map [ᐞ](#table-of-contents)

<a id="environment-map"></a>

Inspect every affected variant, including effective override behavior:

* Root `Dockerfile` and `.dockerignore`: shared PHP `base`, developer tooling
  and non-root user in `development`, and final default `production` stage.
* `compose.yaml`: PHP development build, bind mounts, runtime user, tmpfs,
  nginx, MariaDB named storage, and optional developer-facing services.
* `compose.prod.yaml`: local production override with development mounts
  replaced, production PHP target, read-only application configuration,
  health-gated dependencies, and shared application volume for nginx.
* `docker/ci-compose.yaml`: development PHP with the application entrypoint
  disabled, an idle command, Composer cache, and CI PHP configuration.
* `.devcontainer/devcontainer.json` and
  `.devcontainer/docker-compose.devcontainer.yml`: existing Compose service
  attachment, `/app` workspace, UID/GID, remote user, ports, and lifecycle hooks.
* `docker-entrypoint-dev.sh` and `docker-entrypoint.sh`: configuration checks,
  dependency/key/database initialization, migrations, final process execution,
  and production asset publication.
* `docker/php/`, `docker/nginx/`, and `docker/mariadb/`: image builds,
  development/production PHP and FPM settings, nginx upstream/TLS, and database
  configuration. Inspect safe configuration, never private TLS/JWT key contents.
* `Makefile`, CI bootstrap, [Dev Container guide](../../../.devcontainer/README.md),
  [Docker guide](../../../docker/README.md), and
  [local production guide](../../../doc/DOCKER_MULTI_PLATFORM.md#running-production-locally):
  build/start commands, routing, variant selection, and operator instructions.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Establish the affected variants and acceptance criteria: build contents,
   startup behavior, service connectivity, filesystem access, tooling, or IDE
   attachment. Inspect running services and existing local work before editing.
   Ask before changing exposed ports, service names, privileges, mounted data,
   database compatibility, or lifecycle side effects. A Dockerfile change is
   not authorization to rebuild/recreate a shared environment.
2. Keep shared requirements in `base`, development-only tools in `development`,
   and production requirements in `production`. Preserve production as the
   final stage so a build without `--target` does not ship developer tooling.
   Check extensions and packages against Composer requirements and actual
   callers; do not install dependencies speculatively. Use the image skill
   when external `FROM` or `COPY --from` pins also change.
3. Distinguish built files from runtime mounts. Development bind mounts can
   conceal image contents, while production relies on copied files and
   installed non-development dependencies. Keep `.dockerignore`, `COPY`
   exclusions, Composer scripts, PHP/FPM configuration copies, and Compose
   mounts aligned. Never copy private overrides, credentials, JWT keys, or
   local TLS keys into images or build logs. Do not rely on a mounted
   development file to prove the production image is complete.
4. Review merged Compose configuration, not override files alone. Local
   production uses `!reset` and `!override` to replace development settings;
   preserve those semantics and the required Compose support. Trace volumes,
   environment, user, ports, tmpfs, dependencies, and host mappings in each
   variant. Do not leave source bind mounts, debugger settings, development
   TLS/status routes, or developer tools enabled in production accidentally.
5. Preserve user and filesystem contracts. Match build-time `HOST_UID`/
   `HOST_GID`, Compose runtime user, Dev Container `remoteUser`, home paths,
   writable cache/tmpfs ownership, and FPM worker configuration. Test writes
   as the intended user; running a check as root can hide permission defects.
   Do not add blanket permissions, root execution, privileged mode, or Docker
   socket access to bypass an ownership or tooling problem.
6. Preserve shell and tool availability across interactive and noninteractive
   callers. The development image configures Python in `/home/dev/.venv`;
   Node uses nvm, and Bash/Fish initialization differ from `exec -T`.
   Check the actual invocation path rather than assuming a login-shell test
   proves Make, IDE, or CI execution. Do not install into system Python or
   add a separate documentation image when the shared development image owns
   the capability.
7. Treat entrypoints as mutating operations. The development entrypoint
   installs dependencies, generates JWT keys, creates the database, and runs
   migrations before `exec`. Production initialization runs when the command
   is `php-fpm`, generates keys, migrates, installs assets, and refreshes the
   nginx shared application directory. Preserve command gating, failure
   propagation, ownership, and signal handling; do not run initialization
   merely to validate syntax. Obtain approval before startup that can replace
   keys, apply migrations, or modify existing data.
8. Keep CI startup distinct from application startup. Its override skips
   entrypoint initialization and keeps the development service available for
   explicit bootstrap commands. Preserve coverage settings, cache mounts,
   environment forwarding, and host database reachability. CI uses the same
   development image, not evidence that the production entrypoint works.
9. Trace service readiness and networking. `depends_on` alone is not a
   readiness check; local production gates PHP/nginx on health checks.
   A listening FPM port does not prove database access, migrations, or HTTP
   behavior. Keep nginx document roots/upstreams and the production shared
   application copy consistent. Never delete persistent MariaDB volumes,
   prune images, stop unrelated containers, or use `down -v` as validation.
10. Update affected setup/rebuild instructions, service/port inventories,
    Dev Container settings, and CI callers together. Dev Container rebuilds
    and post-create hooks can install dependencies or stop shared Compose
    services; disclose those effects before invoking them. Use
    [update-application-configuration](../update-application-configuration/SKILL.md)
    for application settings and preserve private overrides.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

For Compose changes, run the affected variants from the repository root on the
Docker host. Quiet validation avoids dumping resolved configuration:

```bash
docker compose config --quiet
docker compose -f compose.yaml -f compose.prod.yaml config --quiet
docker compose -f compose.yaml -f docker/ci-compose.yaml config --quiet
docker compose -f compose.yaml -f .devcontainer/docker-compose.devcontainer.yml config --quiet
```

For Dockerfile changes, build the affected targets on the host using the image
skill's commands; build both PHP targets when shared base behavior changes.
Build success does not prove startup, mounted configuration, or permissions.
For entrypoint edits, check Bash syntax inside the development container and
add focused regression coverage using the tooling skill without executing
destructive initialization.

For approved runtime verification, use a non-production environment with
explicitly authorized data and lifecycle actions. Check effective PHP
extensions/settings, FPM/nginx configuration, intended-user writes, readiness,
and affected HTTP behavior. Run relevant application tests when runtime
behavior changes. Confirm CI tool execution separately from application startup;
confirm IDE discovery, attachment, workspace/user, and hooks in the intended
client when Dev Container behavior changes. Static Compose/JSON checks do not
prove IDE operation or multi-platform compatibility.

For this skill's Markdown alone, run from the repository root on the Docker
host with development services running:

```bash
make lint-markdown
make check-skills
```

Report static checks, builds, runtime checks, and IDE verification separately,
including unavailable variants and any required operator rebuild. Do not claim
running containers use edited image contents before rebuilding/recreating them.
Adding this skill does not rebuild images, restart services, migrate data, or
execute its described runtime checks.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
