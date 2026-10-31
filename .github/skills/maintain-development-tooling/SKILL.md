---
name: maintain-development-tooling
description: Maintain repository scripts, Make targets, checker contracts, container forwarding, and offline script regression tests while preserving exit behavior, execution boundaries, and generated outputs.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when changing repository development scripts, Make targets,
checker behavior, command routing, or script regression tests. It owns tooling
behavior, not dependency upgrades or application features. Use
[update-github-workflows](../update-github-workflows/SKILL.md) for CI job
behavior, [update-github-actions](../update-github-actions/SKILL.md) for action
pins, and [update-docker-images](../update-docker-images/SKILL.md) for image
inspection and pins. Use
[maintain-ai-guidance](../maintain-ai-guidance/SKILL.md) for skill and instruction
contracts rather than expanding a checker to impose new policy implicitly.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Tooling path map](#tooling-path-map)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Tooling path map [ᐞ](#table-of-contents)

<a id="tooling-path-map"></a>

Inspect the implementation and its actual callers:

* `Makefile`: host-target allowlist, forwarding, Compose selection, environment
  propagation, help, task recipes, runner/report paths, and exit decoration.
* [Scripts guide](../../../scripts/README.md) and scripts in `scripts/`:
  documentation checking, action/tool update discovery, workflow validation,
  Docker image helpers, and project statistics.
* `scripts/tests/make-container-test.sh`,
  `scripts/tests/docker-images-test.sh`, and
  `scripts/tests/check-workflow-tool-updates-test.py`: existing offline
  fixtures, command mocks, failure assertions, and temporary-file handling.
  Do not assume every script already has a regression suite.
* `.github/workflows/` and `.github/actions/`: direct and Make-mediated callers,
  inputs, invocation environment, and consumers of output and exit status.
* `Dockerfile`, `docker/ci-compose.yaml`, `.devcontainer/`, and tool manifests
  when affected: available interpreters, executables, paths, and mounts.
* [Command reference](../../../doc/COMMANDS.md),
  [testing guide](../../../doc/TESTING.md#selecting-validation), and
  [CI guide](../../../doc/DEVELOPMENT.md#continuous-integration): documented
  commands, validation scope, and reproduction instructions.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Define the command contract: inputs/options and environment overrides,
   defaults, working directory, output streams and shapes, exit statuses,
   side effects, network needs, and supported execution environments.
   Trace direct script, Make, and CI callers before changing it. Ask before
   altering failure policy, destructive behavior, supported platforms,
   interactive defaults, or public command names.
2. Extend the existing owning script or target rather than introducing a
   parallel checker or wrapper. Inspect the shebang and actual interpreter;
   Bash arrays and process substitution are not portable `sh`. Preserve
   executable modes when a caller invokes a script directly, and verify
   repository-relative paths from the supported working directory.
3. Keep project tooling inside the Compose PHP development container, using
   host-safe Make targets. Docker orchestration, registry inspection, and
   GitHub platform operations remain host-side. In the Makefile,
   `HOST_TARGETS` excludes host-only commands from forwarding; other explicit
   goals forward through `COMPOSE_EXEC` with `exec -T` to `php make`.
   Preserve mixed goals, default/help behavior, recursive Make, and dry runs
   without creating routing loops or running a host tool inside the container.
4. Preserve container detection and CI selection as separate concerns.
   `/.dockerenv` determines the current container context; `GITHUB_WORKFLOW`
   selects the CI Compose override, not proof of running inside a container.
   Forward intended environment names through the existing `CONTAINER_ENV`
   allowlist without interpolating secret values into command arguments.
   Preserve UID/GID and required configuration visibility; never fall back
   to host PHP, Composer, Python, or Node when the container is unavailable.
5. Treat exit status as part of the API. Distinguish invalid arguments,
   missing prerequisites, lookup failures, discovered issues, available
   updates, and success using the owning tool's existing contract.
   `check-action-updates.sh` distinguishes updates from discovery failures,
   while its Make target deliberately decorates handled statuses 1 and 2
   through `RUN_WITH_DECORATED_EXIT_OUTPUT`. A Make success is therefore not
   proof that direct checker output reported no issues. Do not copy that
   informational behavior into blocking checks or silently change its callers.
   Verify script and wrapper outcomes separately; Make may translate a failing
   recipe's exit status into its own nonzero status.
6. Preserve useful diagnostics and output consumers. Keep stdout/stderr,
   noninteractive behavior, color/`NO_COLOR`, summaries, and machine-readable
   inventories compatible where used. Do not let pipelines, subshells,
   command substitutions, or output decoration conceal a failed command.
   Avoid printing configuration or secret values. For network operations,
   preserve bounded retries, timeouts, pagination termination, explicit
   failures, and required checksum verification; a failed lookup is not
   an empty successful result.
7. Keep checks read-only unless mutation is explicitly part of the approved
   command. Use isolated temporary fixtures and cleanup limited to their
   resolved paths. Mock Docker, registry, Git, and other external commands
   through existing override/PATH patterns in regression tests; unexpected
   calls should fail rather than reach a real service. Do not validate routing
   by actually invoking destructive targets, installing dependencies,
   regenerating JWT keys, or stopping shared services.
8. Add regression cases for the changed boundary: successful and invalid
   input, missing tools/files, malformed upstream data, subprocess failure,
   and affected output/exit behavior. For discovery changes, cover relevant
   duplicates, ignored paths, file variants, anchors, or index relationships.
   For routing changes, cover local/CI forwarding, mixed goals, dry runs,
   environment isolation, and failure propagation. Reuse fixtures without
   duplicating the production algorithm in test expectations.
9. Exercise actual report or test-runner outputs when those contracts change.
   A mocked invocation does not prove PHPUnit discovery, coverage generation,
   report paths, or uploader compatibility. Use
   [write-tests](../write-tests/SKILL.md) for PHPUnit behavior and the workflow
   skill for CI consumers. A shell/Python checker change alone does not require
   application PHPUnit or PHP static analysis.
10. Update help, relevant Make descriptions, the scripts guide, command
    reference, and CI documentation where their contracts change. Verify
    examples against current code; documentation can lag actual container
    routing. Label host/container execution and distinguish local checks
    from hosted CI runs. Use
    [update-dependencies](../update-dependencies/SKILL.md) for justified tool
    dependency changes rather than downloading an alternative ad hoc.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

Select only checks relevant to changed executable behavior. From the repository
root on the Docker host with development services running, existing regression
entry points include:

```bash
make test-make-routing
make test-docker-images
docker compose exec -T php python3 scripts/tests/check-workflow-tool-updates-test.py
```

These are offline mocked checks. Run the affected one, not every suite for an
unrelated script change. The routing suite explicitly emulates host execution;
its passed mocks do not prove a real Docker daemon or application is healthy.
If a checker has no suite, add focused fixtures in `scripts/tests/` using
existing test mechanisms instead of installing a new framework.

For changed Bash scripts, check syntax inside the container with `bash -n`
against each exact changed path. Use existing relevant lint commands;
`make lint-workflows` validates workflow shell steps and its own validator
script, not every shell script in the repository. Syntax checks alone do not
verify behavior. Exercise the real read-only caller where feasible, and
explicitly distinguish network-dependent checks from offline tests.

For this skill's Markdown alone, run from the repository root on the Docker
host with development services running:

```bash
make lint-markdown
make check-skills
```

Review final diffs, new files, executable modes, and generated artifacts.
Report exact checks and blockers; do not claim a mocked command executed
real builds, registry requests, or application tests. Adding this skill does
not change tooling or mean its described regression commands were executed.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
