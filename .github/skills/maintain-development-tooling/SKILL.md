---
name: maintain-development-tooling
description: Review and improve shell and Python script readability, repository scripts, Make targets, checker contracts, container forwarding, and offline regressions while preserving behavior and execution boundaries.
---

# What is this?

<a id="what-is-this"></a>

Use this skill for shell and Python readability reviews, development scripts,
Make targets, checker behavior, command routing, or script regression tests.
Dependency upgrades and application features are outside its scope.

Use [document-shell-scripts](../document-shell-scripts/SKILL.md) for shell-script
file headers and explanatory comments.

For `.py` docs, use [document-python-scripts](../document-python-scripts/SKILL.md).

Use [maintain-repository-scripts](../maintain-repository-scripts/SKILL.md) for adding
or changing scripts under `scripts/`, including tests and callers. Use
[update-github-workflows](../update-github-workflows/SKILL.md) for CI job behavior,
[update-github-actions](../update-github-actions/SKILL.md) for action pins, and
[update-docker-images](../update-docker-images/SKILL.md) for image inspection and
pins. Use [maintain-ai-guidance](../maintain-ai-guidance/SKILL.md) for skill and
instruction contracts rather than expanding a checker to impose new policy
implicitly.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and [AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Tooling path map](#tooling-path-map)
    * [Workflow](#workflow)
    * [Script readability reviews](#script-readability-reviews)
      * [Shell review checks](#shell-readability-reviews)
      * [Python review checks](#python-readability-reviews)
    * [Validation](#validation)

## Tooling path map [ᐞ](#table-of-contents)

<a id="tooling-path-map"></a>

Inspect the implementation and its actual callers:

* `Makefile`: host-target allowlist, forwarding, Compose selection, environment
  propagation, help, task recipes, runner/report paths, and exit decoration.
* [Scripts guide](../../../scripts/README.md) and scripts in `scripts/`: documentation
  checking, action/tool update discovery, workflow validation, Docker image
  helpers, and project statistics.
* `scripts/make/tests/make-container-test.sh`,
  `scripts/docker/tests/docker-images-test.sh`, and
  `scripts/checks/tests/check-workflow-tool-updates-test.py`: representative
  offline fixtures, command mocks, failure assertions, and temporary-file
  handling. Focused tests live in their owning script group's `tests/`
  directory; `scripts/tests/` is reserved for cross-group runners. Some tests
  derive helper paths from the script under test or construct fixture layouts;
  inspect those path assumptions when moving scripts or helpers. Do not assume
  every script already has a regression suite.
* `.github/workflows/` and `.github/actions/`: direct and Make-mediated callers,
  inputs, invocation environment, and consumers of output and exit status.
* `Dockerfile`, `docker/ci-compose.yaml`, `.devcontainer/`, and tool manifests
  when affected: available interpreters, executables, paths, and mounts.
* [Command reference](../../../doc/COMMANDS.md), [testing guide](../../../doc/TESTING.md#selecting-validation),
  and [CI guide](../../../doc/DEVELOPMENT.md#continuous-integration): documented
  commands, validation scope, and reproduction instructions.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Define the command contract: inputs/options and environment overrides,
   defaults, working directory, output streams and shapes, exit statuses, side
   effects, network needs, and supported execution environments. Trace direct
   script, Make, and CI callers before changing it. Ask before altering failure
   policy, destructive behavior, supported platforms, interactive defaults, or
   public command names. For changes that may affect Make commands, list the
   affected invocations and capture their baseline before editing. Rerun them
   after the final edit and report the differences using the [Make comparison guide](../../../doc/TESTING.md#comparing-make-commands).
2. Extend the existing owning script or target rather than introducing a
   parallel checker or wrapper. Inspect the shebang and actual interpreter; Bash
   arrays and process substitution are not portable `sh`. Preserve executable
   modes when a caller invokes a script directly, and verify repository-relative
   paths from the supported working directory. When moving or renaming a script
   or helper, audit path-derived references in callers and tests, including
   sibling lookups, `__file__`/`dirname` parent calculations, and copied fixture
   layouts; update them to match the new production tree.
3. Keep project tooling inside the Compose PHP development container, using
   host-safe Make targets. Docker orchestration, registry inspection, and GitHub
   platform operations remain host-side. In the Makefile, `HOST_TARGETS`
   excludes host-only commands from forwarding; other explicit goals forward
   through `COMPOSE_EXEC` with `exec -T` to `php bash scripts/make/make.sh`,
   filtering container-side Make recipe errors. Application recipes must not add
   a second host-forwarding path; keep native commands in the recipe and routing
   in the central dispatch. Add target metadata through `REGISTER_TARGET` (name,
   group, execution, risk, label) and keep its `##` description beside the
   recipe. The registry derives `HOST_TARGETS`, `WRAPPED_TARGETS`, and labels;
   help uses the same groups and markers. Do not maintain parallel target lists
   or name-based help rules. Registered commands are declared `.PHONY` through
   `COMMAND_TARGETS`; preserve execution despite same-named files, including raw
   recursive recipes. Help accepts `GROUP` filtering without Docker access;
   preserve the full default inventory and routine/overwrite/destructive
   ordering within groups. `scripts/make/compose-context.sh` performs on-demand
   project/service checks: running PHP is required for forwarding/shells;
   unhealthy PHP warns but remains accessible, and stopped containers remain
   eligible for logs. Preserve mixed goals, default/help behavior, recursive
   Make, and dry runs without creating routing loops or running a host tool
   inside the container.
4. Preserve container detection and CI selection as separate concerns.
   `/.dockerenv` determines the current container context; `GITHUB_WORKFLOW`
   selects the CI Compose override, not proof of running inside a container.
   Forward intended environment names through the existing `CONTAINER_ENV`
   allowlist without interpolating secret values into command arguments.
   Preserve UID/GID and required configuration visibility; never fall back to
   host PHP, Composer, Python, or Node when the container is unavailable.
5. Treat exit status as part of the API. Distinguish invalid arguments, missing
   prerequisites, lookup failures, discovered issues, available updates, and
   success using the owning tool's existing contract. `check-action-updates.sh`
   distinguishes updates from discovery failures, while its Make target
   deliberately decorates handled statuses 1 and 2 through
   `scripts/output/action-update-output.sh`. A Make success is therefore not
   proof that direct checker output reported no issues. Do not copy that
   informational behavior into blocking checks or silently change its callers.
   Verify script and wrapper outcomes separately; Make may translate a failing
   recipe's exit status into its own nonzero status.
6. Preserve useful diagnostics and output consumers. Keep stdout/stderr,
   noninteractive behavior, color/`NO_COLOR`, summaries, and machine-readable
   inventories compatible where used. Do not let pipelines, subshells, command
   substitutions, or output decoration conceal a failed command. Avoid printing
   configuration or secret values. For network operations, preserve bounded
   retries, timeouts, pagination termination, explicit failures, and required
   checksum verification; a failed lookup is not an empty successful result.
   Reuse `command-output.sh --run` for finite commands, complete fail-fast
   recipes, and information output; use `--stream` for directly attached shells
   and logs without completion banners. Do not duplicate these execution modes
   in inline shell wrappers or buffer native output. Use `--status` for
   standalone messages rather than inline sourcing; it is notification-only, so
   failure guards must explicitly exit nonzero. `COMPOSE_RUN` shares lifecycle
   UID/GID and completion decoration without replacing target-specific guards,
   warnings, or Compose arguments. Derive native tool flags from the shared
   color policy, with nonempty `NO_COLOR` taking precedence. Keep GitHub
   analyzer formats plain. Use the existing SGR-only native-output filter only
   when native disable controls are unavailable or unreliable; never filter
   generated report files. Use `report-output.sh` to check declared local
   outputs before announcing paths. Distinguish analyzer/client completion from
   upload confirmation; preserve Shepherd's non-blocking upload behavior and
   native diagnostics.

   The Make dispatcher adds two spaces to ordinary wrapped-target output lines
   on stdout and stderr. GitHub workflow commands stay at column zero so
   annotations remain active. The opening `[RUN]` and final outcome stay at the
   same level, without a blank line before a top-level `[OK]` or `[FAIL]`.
   Nested result spacing and direct helper calls retain their own contracts.
   Wrapped target output removes whitespace padding immediately before native
   `[OK]` lines and at end of stream. Self-reporting direct targets own their
   hierarchy by indenting nested sections and scenario statuses consistently,
   and closing completed sections with an outcome. In grouped output, put the
   first nested `[RUN]` below its parent heading; keep each outcome with its
   check. Use one blank line between completed sibling checks and the next
   `[RUN]`, but none before the final aggregate outcome. If a caller adds a
   separator, set `OUTPUT_RUN_GROUPED_SEPARATOR=0` to avoid doubling it. Clear
   `OUTPUT_RUN_GROUPED` for independent regression processes so output contracts
   stay isolated. Test stream indentation and outcome levels; direct command
   helper callers retain child output. The local pre-PR runner passes each
   nested Make target as `OUTPUT_RUN_TARGET`; wrapped labels add the target name.
   `command-output.sh` adds the suffix to direct target labels without changing
   child indentation. Direct Make targets indent native output explicitly when
   needed, and self-reporting targets own the same hierarchy in standalone and
   grouped output. Register concise, human-readable pre-PR labels before the
   `(make <target>)` suffix. Self-reporting direct targets should use descriptive
   labels. The local pre-PR runner filters timestamped Monolog `deprecation.INFO`
   records from nested Make stderr only; preserve other diagnostics and direct
   Make behavior. `make help` uses the same two-space output indentation: its
   section headings and introductory lines are indented two spaces, and target
   entries are indented four spaces beneath them; shared top-level statuses stay
   unindented.

   The pre-PR development and production image builds use host-only
   `docker-build-pre-pr-development` and `docker-build-pre-pr-production`
   targets. Preserve their local tags, Buildx flags, production cache policy,
   plain progress output, grouped output, and fail-fast order when changing the
   runner or targets. Plain Buildx progress avoids broken interactive redraws
   through the output wrapper without hiding build diagnostics.

   For `make ci-detect-changes`, the dispatcher indents the section title two spaces,
   its introductory lines, group headings and notes four spaces, and job entries
   six spaces. The final note is immediately followed by the wrapper outcome.
   The Make caller sets `CI_JOB_PREVIEW_INDENT` for only the introductory lines
   and groups the target's outcome; do not shift the whole section or alter the
   direct detector and GitHub Actions output by default. Preserve the separator
   between a completed preview or check and the next sibling `[RUN]`, without
   adding one within the completed check.
7. Keep checks read-only unless mutation is explicitly part of the approved
   command. Use isolated temporary fixtures and cleanup limited to their
   resolved paths. Mock Docker, registry, Git, and other external commands
   through existing override/PATH patterns in regression tests; unexpected calls
   should fail rather than reach a real service. Do not validate routing by
   actually invoking destructive targets, installing dependencies, regenerating
   JWT keys, or stopping shared services.
8. Add regression cases for the changed boundary: successful and invalid input,
   missing tools/files, malformed upstream data, subprocess failure, and
   affected output/exit behavior. For discovery changes, cover relevant
   duplicates, ignored paths, file variants, anchors, or index relationships.
   For script/helper moves, make fixtures preserve or explicitly resolve the
   real directory relationships instead of assuming moved files remain siblings.
   Run the owning regression suite and any aggregate/CI entry point that
   includes it. For routing changes, cover local/CI forwarding, mixed goals, dry
   runs, environment isolation, and failure propagation. Reuse fixtures without
   duplicating the production algorithm in test expectations. For streaming
   fixtures, separate the output assertion from teardown and cover inherited
   ignored SIGINT. Use isolated process groups and bounded cleanup of all
   descendants, not just the group leader; see the [streaming fixture guidance](../../../doc/TESTING.md#command-output-contracts).

   For visual output changes, assert the requested whitespace literally at the
   real caller boundary, including representative section headings, nested
   entries, notes, and adjacent statuses. Capture stdout and stderr together when
   checking their combined layout: separate assertions cannot prove spacing
   across streams. Also check the individual streams when their destinations
   matter, and cover relevant standalone and grouped callers, failures, and color.
   Compare direct script and CI output when a change is intended only for Make;
   preserve unaffected lines instead of applying a blanket indentation adjustment.
   Exercise the read-only Make command after the final edit in addition to
   isolated fixtures.
9. Exercise actual report or test-runner outputs when those contracts change. A
   mocked invocation does not prove PHPUnit discovery, coverage generation,
   report paths, or uploader compatibility. Use [write-tests](../write-tests/SKILL.md)
   for PHPUnit behavior and the workflow skill for CI consumers. A shell/Python
   checker change alone does not require application PHPUnit or PHP static
   analysis.
10. Update help, relevant Make descriptions, the scripts guide, command
    reference, and CI documentation where their contracts change. Verify
    examples against current code; documentation can lag actual container
    routing. Label host/container execution and distinguish local checks from
    hosted CI runs. Use [update-dependencies](../update-dependencies/SKILL.md) for
    justified tool dependency changes rather than downloading an alternative ad
    hoc.

## Script readability reviews [ᐞ](#table-of-contents)

<a id="script-readability-reviews"></a>

For repository-wide reviews, inventory tracked files for the requested language
from the repository root on the Docker host:

```bash
git ls-files '*.sh'
git ls-files '*.py'
```

Read every inventoried file, including entrypoints, helpers, runners, and
regression fixtures. Inspect actual callers in Make, CI, Docker, and other
scripts as relevant. Do not execute or import a script merely to review it:
entrypoints, imports, and tests can have side effects.

Return a backlog grouped by exact file path. For each file, give concrete
observations with function names or line references, proposed improvements,
priority, status, and the affected validation boundary. Mark files with no
justified change as reviewed with no action; do not manufacture a refactor to
fill the inventory. Separate readability work from correctness findings and
intentional behavior changes. A review request alone does not authorize
implementing the backlog or changing command contracts.

Use this readability rubric when assessing each finding:

* **Scanability:** Can a maintainer quickly see the script's phases, branching,
  and validation/execution/reporting order?
* **Names and state:** Do names explain intent, and are variable scope, state
  changes, side effects, and outcome codes easy to follow?
* **Commands and data flow:** Are quoting, optional arguments, pipelines,
  subprocesses, and input/output boundaries legible without hiding behavior?
* **Abstraction and explanation:** Do functions and comments clarify meaningful
  units and non-obvious behavior without fragmenting simple flows or restating
  syntax?

Label each proposed improvement as a **quick win** when it is a localized
readability change that preserves the script's structure, or **structural** when
it requires materially reorganizing control flow, state, or helper boundaries.
Give the reason and a concrete improvement for each label; keep priority and
validation separate. Report correctness or safety issues as such, not as
readability findings. Prefer actionable, behavior-preserving proposals and do
not treat shorter source or mechanical reformatting as improvement by itself.

Assess these concerns against the actual code:

* Treat human readability as a primary acceptance criterion, not merely fewer
  lines or extracted functions. Use blank lines to separate logical steps across
  all statement types, including setup, computations, calls, control flow, and
  validation, execution, and reporting. Keep closely related statements together
  when they form one step; do not insert blank lines mechanically between every
  statement. Review the final source for visual breathing room and behavior preservation.
* For a whitespace-focused review, inspect complete function bodies and test
  scenarios for crowded transitions, considering each boundary rather than
  matching only specific statement pairs. Separate a setup or computation from a
  distinct subsequent action, even when the previous statement spans multiple
  lines or the next immediately uses its value. For example, `status = 0` and a
  following `for` loop need an empty line between them. Also inspect transitions
  before and after control-flow blocks and between test setup, execution, and
  assertions. Keep related assignments or calls together when they form one
  logical step. Record precise locations and the intended separation in the
  review backlog, even when no structural refactor is needed. Mark already
  readable files as reviewed without action.
* Blank lines in shell heredocs, quoted strings, and Python multiline strings
  are data, not visual spacing. Do not insert lines there or inside continued
  shell commands, pipelines, or Python expressions without verifying their
  semantics. Preserve fixture output, indentation, and surrounding control flow;
  review whitespace-only diffs against the original executable content rather
  than assuming all whitespace is harmless.
* Prefer descriptive names, explicit phases, readable multiline commands, and
  focused functions for substantial parsing, validation, execution, or reporting
  blocks. Keep short linear wrappers simple; do not extract every command,
  impose arbitrary function/file limits, or split scripts by size alone.
* Remove repeated algorithms through the closest existing owner. Extract a
  helper only when it has a clear responsibility and reduces cognitive load;
  avoid extra files or generic dispatch frameworks for single-use logic.
* Make state and data flow visible: keep state local where possible, identify
  intentional shared state and side effects, and name internal outcome codes.
  Explain non-obvious delimiters and regexes. Replace misleading comments and
  redundant steps rather than narrating each operation.
* Preserve interpreter, working-directory assumptions, argument boundaries,
  failure propagation, signals, cleanup, output streams, color controls, and
  machine-readable data.
* Keep test setup, action, and assertions distinguishable. Use named scenarios
  and reusable fixture builders where they remove repetition; unexpected mock
  calls must still fail. Preserve coverage and failure diagnostics, including
  partial lines, TTY behavior, and interrupted commands where relevant.

Implement approved items in bounded changesets, preserving the
[shared branch and publication rules](../shared-guidance.md#preparing-a-change).
Establish focused behavior coverage before restructuring a complex script. Use
the validation section below; documentation checks alone do not prove script
behavior or live CI execution.

### Shell review checks [ᐞ](#table-of-contents)

<a id="shell-readability-reviews"></a>

Include root entrypoints, `docker/` scripts, and sourced integrations. Preserve
sourced-versus-executed behavior, pipeline and subshell statuses, and traps. Use
argument arrays where they clarify quoting and optional flags. Explain
non-obvious embedded awk or jq. Do not add blanket `set -euo pipefail` to
sourced helpers or status-collecting wrappers; Bash conditional execution can
also disable `errexit` in functions.

### Python review checks [ᐞ](#table-of-contents)

<a id="python-readability-reviews"></a>

Trace direct execution, imports, and runner-loaded tests. Preserve import-time
behavior, exception handling, subprocess arguments and status handling, and
fixture cleanup. Check that fixture paths and mocks still match real callers.
Narrow optional values before use, including regex matches before `.group()`.
Use an explicit guard or assertion that Pyright can recognize; do not suppress
the diagnostic or assume a passing fixture proves the value cannot be `None`.
Use [document-python-scripts](../document-python-scripts/SKILL.md) for headers,
function contracts, and explanatory comments.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

Select only checks relevant to changed executable behavior. From the repository
root on the Docker host with development services running, existing regression
entry points include:

```bash
make test-make-routing
make test-docker-images
docker compose exec -T php python3 scripts/checks/tests/check-workflow-tool-updates-test.py
```

These are offline mocked checks. Run the affected one, not every suite for an
unrelated script change. The routing suite explicitly emulates host execution;
its passed mocks do not prove a real Docker daemon or application is healthy. If
a checker has no suite, add focused fixtures in its owning
`scripts/<group>/tests/` directory using existing test mechanisms instead of
installing a new framework.

Run the consolidated output-contract checks when changing Make target
registrations or recipe ordering, composing regression runners, or changing
suite output such as group headings, scenario counts, or completion summaries.
From the repository root on the Docker host with the PHP development service
running:

```bash
make test-command-output
```

Trace these changes into `scripts/output/tests/command-output-test.py`,
`scripts/make/tests/make-information-output-test.py`, and
`scripts/checks/tests/lint-workflows-output-test.py` as applicable. Preserve Make
registration, recipe, and help ordering; update expected suite output and
isolated runner fixtures together. A newly invoked regression script must be
represented in affected fixtures, with failures still propagated. Focused
producer tests and `make lint-workflows` do not replace these consumer checks.
See [command output contracts](../../../doc/TESTING.md#command-output-contracts)
for coverage and limits.

For changed Bash scripts, check syntax inside the container with `bash -n`
against each exact changed path. Inspect the ShellCheck invocation in
`scripts/checks/lint-workflows.sh` and run `make lint-workflows` when any changed
script is listed there. That validator also checks workflow and composite action
shell, but does not check every shell script in the repository. Use other
relevant lint commands for scripts outside that list; syntax checks and behavior
tests do not replace lint. Exercise the real read-only caller where feasible,
and distinguish network-dependent checks from offline tests.

For executable Python changes, compile each changed file in memory inside the
PHP development container without importing it or writing bytecode. For example,
from the repository root on the Docker host:

```bash
docker compose exec -T php python3 -c '
from pathlib import Path
import sys

for filename in sys.argv[1:]:
    compile(Path(filename).read_bytes(), filename, "exec")
' scripts/checks/check-skills.py
```

Replace the example path with the exact changed paths. Run the affected
regression suite or real read-only caller as appropriate; compilation alone does
not check imports, runtime behavior, or output contracts.

For any executable Python change under `scripts/`, including regression tests
and test runners, also run the existing CI lint and type checks after the final
edit. From the repository root on the Docker host with the PHP development
service running:

```bash
make lint-python
make typecheck-python
```

These targets run Ruff and Pyright inside the container. `pyrightconfig.json`
includes all of `scripts/`. Syntax compilation and passing regression tests,
including `make lint-workflows`, do not replace lint or type analysis. Preserve
the configured scope and diagnostics; do not add casts, ignores, or exclusions
to hide errors. Inspect the selected configuration-check steps in
`.github/workflows/main.yml` before choosing local validation, and retain
separate outcomes for syntax, regressions, and type checking. A missing tool or
failed required check is a blocker, not evidence that validation passed.

For this skill's Markdown alone, run from the repository root on the Docker host
with development services running:

```bash
make lint-markdown
make check-skills
```

When requested, run either target from the repository root on the Docker host
with development services running. The CI-selected target is:

```bash
make pre-pr-checks
```

It applies CI's changed-file selection to the committed `BASE_SHA...HEAD_SHA`
range (defaults: `HEAD^` and `HEAD`). Uncommitted changes do not affect
selection. Empty or uncertain comparisons select all local checks; set both
variables to inspect another committed range.

To run every locally reproducible check regardless of changed paths or commit
range, use:

```bash
make pre-pr-checks-all
```

The all-checks target always builds both image targets; selected mode builds
them when the Docker category is selected. Either target can overwrite generated
reports; image builds overwrite local tags and require Docker Buildx and
registry access.

It omits GitHub-hosted checks, including Dependency Review, the Symfony Security
Checker action, Gitleaks, Trivy scans/SARIF uploads, and optional external
reporting. Scheduled and informational jobs are also excluded. It is not
required for ordinary scoped tooling changes and does not prove hosted CI passed.

Review final diffs, new files, executable modes, and generated artifacts. Report
exact checks and blockers; do not claim a mocked command executed real builds,
registry requests, or application tests. Adding this skill does not change
tooling or mean its described regression commands were executed.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
