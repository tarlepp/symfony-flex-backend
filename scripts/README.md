# What is this?

<a id="what-is-this"></a>

This directory contains different scripts that are used during development.

<a id="table-of-contents"></a>

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Resources](#resources)
      * [Command status output](#command-status-output)
      * [Output contract regressions](#output-contract-regressions)
      * [Project stats script](#project-stats-script)
      * [Skill documentation checker](#skill-documentation-checker)
      * [GitHub Actions update checker](#github-actions-update-checker)
      * [GitHub workflow validator](#github-workflow-validator)
      * [Docker multi-platform image helper](#docker-multi-platform-image-helper)

## Resources [ᐞ](#table-of-contents)

<a id="resources"></a>

* [Project stats script](project-stats.sh)
  * This script is used to generate simple project stats. It will generate
    output with some basic stats about project.
* [Skill documentation checker](check-skills.py)
  * Validates skill front matter, exact skills-index coverage, and relative
    Markdown links and anchors across repository Markdown files. Dependency
    and generated directories are excluded.
* [GitHub Actions update checker](check-action-updates.sh)
  * Checks pinned GitHub Actions and workflow-tool release declarations, reports
    discovery issues (unpinned refs/conflicting versions), and checks for
    available version updates across major lines. Review compatibility and get
    approval before applying a major-version upgrade suggestion.
* [GitHub workflow validator](lint-workflows.sh)
  * Validates all workflows and their supported shell steps, and separately
    validates local action metadata.
* [Docker multi-platform image helper](docker-images.sh)
  * Checks image version updates, resolves multi-platform image digests,
    verifies pinned base images and builds images for other platforms.

### Command status output [ᐞ](#table-of-contents)

<a id="command-status-output"></a>

`scripts/command-output.sh` owns two command execution modes, both preserving
native stdout/stderr and the command's exit status:

| Mode | Use | Status |
| --- | --- | --- |
| `--run` (default) | Finite recipes and information | Start and completion |
| `--stream` | Shells and followed logs | Start only; execute in place |

`--run` streams output without buffering and reports success only after the
command exits successfully. Information commands use this same mode: status
messages stay on stderr, leaving structured stdout intact. Multi-step commands
must pass a fail-fast executable or the complete nested Make recipe, not a
shell function whose intermediate failures could be hidden by Bash conditional
execution. Make's recipe dispatcher uses explicit `--run` for this boundary.

`--stream` replaces the helper process using `exec`, retaining terminal
descriptors, signals, arguments, and native failures without completion
decoration. Make's `STREAM_COMMAND` uses this shared mode rather than a separate
inline shell implementation. Foreground `start*` remains in `--run` mode because
its contract includes a final session-ended/cancellation status.
Existing `<label> <command> ...` invocations and sourced `output_run` callers
remain compatible.

`--status <status> <message>` prints a single shared status to stderr without
executing a command. It validates its arguments (usage errors exit 64), but
a valid notification always exits zero, even when displaying `[FAIL]`.
Callers must explicitly return failure when the underlying operation failed:
Make's host-context guard prints the message and then exits 1.
Warnings use this entrypoint rather than repeated `bash -c` source/printf
snippets. Report-path macros use `report-output.sh` to validate their files
before printing a shared `[INFO]` message.

`COMPOSE_RUN` shares the UID/GID environment and completion-message wrapper for
finite Docker lifecycle commands. Each target still owns its context guard,
mutation warning, Compose arguments, and precise completion text; shells/logs
continue using `STREAM_COMMAND`. Neither helper changes the command's exit
policy or introduces success before completion.

Read-only examples, from the repository root inside the PHP container (enter
with `make bash` from the Docker host first):

```bash
bash scripts/command-output.sh --run "Read JSON" printf '{"ready":true}\n'
bash scripts/command-output.sh --stream "Native output" printf 'payload\n'
bash scripts/command-output.sh --status INFO "Report path: build/phploc.json"
```

Shared output is used by `test-docker-images`, `check-skills`,
`lint-workflows`, `check-requirements`, `test-make-routing`, and
`docker-image-verify-pins`, and the quality targets below. Other targets retain
their existing output until migrated separately.

Quality targets include `run-tests*`, `infection`, `phpcs`, `ecs*`,
`phpinsights`, `phplint`, `php-parallel-lint`, `psalm*`, `phpstan*`,
`lint-configuration`, `lint-yaml`, `lint-markdown`, `composer-normalize`,
`composer-validate`, `composer-require-checker`, and `composer-unused`.
Information and reporting targets also use the same wrapper: `configuration`,
`project-stats`, `application-info`, `tool-versions`, `check-licenses`, `phploc`,
`report-coveralls`, and `prepare-sonar-reports`.
Each complete recipe, including preparation, prints one `[RUN]` and a
separated final `[OK]` or `[FAIL]`. `run-tests` retains automatic PHP/phpdbg
selection without duplicate summaries. A failed preparation step cannot
produce a success summary. The internal `COMMAND_OUTPUT_RUN` Make variable
prevents recursive decoration; callers should not set it.

Native progress, diagnostics, GitHub annotations, analyzer options and report
paths remain unchanged. Quality summaries report the nested Make exit status
(normally 2 on recipe failure), while its native diagnostics retain the tool's
exit code. Nested Make recipe error lines are filtered before the final status;
the shell integration filters the outer Make error lines. Markdown linting
uses the readable status label `Markdown documentation`.
Markdown rule diagnostics gain a red `[ERROR]` prefix, retaining their
original file/line, rule, and message. The prefix respects the shared color
controls and remains readable without ANSI colors.
Make derives native color flags from the same shared policy: Symfony/Composer
commands use `--ansi` or `--no-ansi`, CodeSniffer and parallel-lint use
`--colors` or `--no-colors`, PHPUnit uses `--colors=always|never`, and plain
Psalm uses `--monochrome`. Infection passes the color choice to its test runner.
Compose receives the same resolved choice through `COMPOSE_ANSI`, without
changing lifecycle commands or service selection. The security check's Composer
dry run also uses the shared ANSI choice.
GitHub-format Psalm/PHPStan analysis always stays plain, independent of status
colors, preserving annotations and JSON report formats.

The installed ECS renderer does not reliably honor `--no-ansi`, and PhpMetrics
hardcodes colors without a disable option. `scripts/native-output.sh` removes
only SGR decoration from their plain-mode output, and from the configuration
check's existing colored messages. It streams stdout/stderr separately and
preserves command failures; filtering failures also fail the target. It never
rewrites report files, annotations, or other terminal controls. Enabled color
executes the native command unchanged. Tools with no force-color support
(including ECS's TTY-only renderer and parallel-lint installations lacking
optional color libraries) retain their native detection; `COLOR=always` does
not manufacture decorations or bypass tool-specific agent safeguards.

* `[RUN]` is blue and means execution started, not success.
* `[OK]` is green and appears only after successful execution/assertions.
* `[WARN]` and `[SKIP]` are yellow; `[FAIL]` is red.
* `[INFO]` is uncolored context, such as a report path, not a success claim.
* Labels remain readable without color. Status messages use stderr, leaving
  child stdout and stderr unbuffered and otherwise unchanged.
* Status colors are owned by `command-output.sh`, not legacy Make printf
  constants. The action-update target calls `action-update-output.sh` directly;
  that script owns its intentional informational exit policy.
* `COLOR=auto` uses the status terminal, excluding `TERM=dumb`. Host Make
  forwarding resolves this before `exec -T` and passes it into the container.
  `COLOR=always` forces ANSI colors; `COLOR=never` disables them. A nonempty
  `NO_COLOR` overrides color settings. Native commands use supported flags or
  the narrow plain-output filter described above.
* The wrapper preserves command exit status, including failures and
  interruptions; Make itself may translate a failed recipe into exit 2.
  Existing informational update-check policies are unchanged.

With development services running, from the repository root on the Docker host:

```bash
make test-docker-images
make test-docker-images COLOR=always
make test-docker-images NO_COLOR=1
```

Native-tool examples use the same controls, from the repository root on the
Docker host with development services running:

```bash
bash scripts/make.sh phpcs COLOR=always
bash scripts/make.sh phpcs COLOR=never
bash scripts/make.sh phpcs COLOR=always NO_COLOR=1
```

For cleaner failure output, use `bash scripts/make.sh` instead of `make`.
The launcher removes only GNU Make's redundant recipe error-location lines
(including recursive Make lines). It preserves `[FAIL]`, native diagnostics,
stdout, and Make's original exit status. Missing targets, Makefile syntax
errors, and other Make diagnostics remain visible. Raw outer `make` invocations
still print their own recipe-error lines; use this launcher or shell integration
to filter them too. Run from the repository root, inside the PHP development
container or on the Docker host with development services running:

```bash
bash scripts/make.sh check-skills
bash scripts/make.sh lint-workflows COLOR=always
```

The launcher does not add result messages to unmigrated targets or change
their failure policies. Color auto-detection happens before stderr filtering.

Shells opened through `make bash` or `make fish` automatically enable filtered
Make output after loading normal user configuration. Bash loads `~/.bashrc`;
Fish uses `--init-command` after its regular startup files. No image rebuild or
user configuration rewrite is needed.
Existing shells and shells opened directly through Docker or the IDE are
unchanged.

To keep typing `make` with filtered output in those shells, opt in per session.
From the repository root, inside the PHP development container or on the
Docker host with development services running:

```bash
source scripts/make-shell.sh
make check-skills
```

This defines a shell function only for the current session. It uses the
launcher when the working directory is this repository's root and delegates
to ordinary Make elsewhere. It does not modify user startup files.
To disable it or bypass it for one command:

```bash
unset -f make
command make check-skills
```

For Fish sessions opened directly on the host, through Docker, or in the IDE,
opt in from the repository root with development services running:

```fish
source scripts/make-shell.fish
make check-skills
```

Disable the Fish function, or bypass it for one command:

```fish
functions --erase make
command make check-skills
```

Central host forwarding invokes `php bash scripts/make.sh`, so container-side
Make recipe-error lines are filtered even when the host uses raw Make.
Filtering the host's outer line still requires the launcher or a shell function.
CI Make commands in `main.yml` and the shared PHP bootstrap use the launcher
explicitly on the runner host. This preserves native diagnostics, annotations,
stdout, exit policies, and existing job gates without installing a global
replacement for Make.

The Makefile's `REGISTER_TARGET` records are the single source for target
help groups, risk markers, execution routing, and wrapped-command labels.
Each record has `name,group,execution,risk,label` fields; the last two are
optional, and the label defaults to the target name. Descriptions stay beside
the owning recipes as `##` comments.
Every registered command is declared `.PHONY` from `COMMAND_TARGETS`, including
help, direct scripts, wrapped recipes, and host lifecycle commands. Same-named
files never make these commands appear up to date. Register commands rather
than adding separate phony lists; actual file-producing build rules should not
be registered as commands. Host forwarding still declares explicit forwarded
goals phony to retain its existing dispatch behavior.

`host` excludes a target from container forwarding; `wrapped` forwards from
the host and decorates the complete container-side recipe; `direct` forwards
but leaves decoration to the owning script. Make derives `HOST_TARGETS` and
`WRAPPED_TARGETS` from those records rather than separate inventories.
The old `QUALITY_TARGETS` name is replaced because wrapped recipes also cover
setup, information, and reports. `COMMAND_LABEL` and `COMMAND_OUTPUT_RUN`
replace the quality-specific internal names; callers should not override them.

Help reads the same records for categories and markers. Everyday information,
tests, quality checks, dependencies, and reports precede setup and host Docker
groups. Each group lists routine commands first, then overwrite commands, then
destructive commands, with target names sorted alphabetically at each risk
level. Missing/invalid help metadata or
descriptions fail explicitly; Make rejects duplicate registrations and invalid
execution modes. Register every new public target and add its recipe description
instead of updating parallel routing lists or help regular expressions.

Application recipes rely exclusively on that central forwarding layer rather
than repeating host/container branches. Native commands, preparation, reports,
and informational exit policies remain in their owning recipes. Docker lifecycle
and interactive commands retain host-side guards and are not forwarded.
`scripts/compose-context.sh` checks Docker access and Compose service state only
when a target needs it; Make parsing and help do not query running containers.
Forwarded commands and Bash/Fish access require only a running PHP service, not
all optional development services. Running PHP with `starting` or `unhealthy`
health emits a yellow warning but remains accessible for troubleshooting.
Stopped, paused, restarting, or absent PHP blocks execution with `[FAIL]`.

Logs require at least one existing PHP, nginx, or MariaDB container, including
stopped containers, so failed startup remains diagnosable. Startup guards query
this Compose project rather than matching global container names; running
services in unrelated projects do not block startup. Production uses its
override for the check; PHP shell access uses the same development/CI Compose
selection as execution. No waiting, health-policy change, or automatic restart
is introduced.

`make test-docker-images` runs offline regression tests of the helper itself,
using temporary Dockerfiles and mocked registry/Docker commands. It does **not**
validate the actual repository Dockerfile pins; its startup notice makes this
distinction explicit. To validate the real pins against the registry, run from
the repository root on the Docker host:

```bash
make docker-image-verify-pins
```

Blue `[RUN]`
headings group manifest validation, version discovery/output formatting,
registry responses/pagination, Dockerfile discovery, lookup failures/retries,
update-pin validation, and aggregate results/empty discovery. Individual
`[OK]` results remain visible beneath each heading. Expected negative
cases are passing tests when their rejection assertions succeed. Case success
is printed after its follow-up assertions, and failures include the scenario,
failed assertion where available, captured helper output, and a final summary.
Fixture-specific color controls do not change the test runner's palette.

Run the output-contract and forwarding regression checks from the same host
directory with development services running:

```bash
docker compose exec -T php python3 scripts/tests/command-output-test.py
docker compose exec -T php python3 scripts/tests/make-check-output-test.py
docker compose exec -T php python3 scripts/tests/lint-workflows-output-test.py
docker compose exec -T php python3 scripts/tests/make-launcher-test.py
docker compose exec -T php python3 scripts/tests/make-quality-output-test.py
docker compose exec -T php python3 scripts/tests/check-outcomes-test.py
make test-make-routing
```

The blocking check targets print `[RUN]` before their native diagnostics and
`[OK]` only after successful completion. Failures print `[FAIL]` with the
original command exit code and still fail Make; statuses 1 and 2 are not
silently accepted. The existing informational `check-action-updates` policy
is unchanged. Wrapper messages use stderr, preserving native stdout.

The Make recipe regression suite above uses offline command fixtures to
exercise success, failures, color controls, and command arguments. It does not
perform live registry validation or run the workflow validators themselves.
To run the real read-only checks, use these commands from the repository root
on the Docker host with development services running:

```bash
make check-skills
make lint-workflows
make check-requirements
make docker-image-verify-pins
```

`lint-workflows` downloads pinned validator releases, and
`docker-image-verify-pins` needs host Docker/buildx and registry access.
The routing target uses offline Docker mocks, not real service health checks.

Workflow validation shows named download/checksum and validation stages instead
of temporary checksum paths, grouped into four numbered sections separated by
blank lines: validator preparation, workflows, local actions, and tooling
scripts. Zizmor's per-file informational progress is quiet
by default, while warnings, findings, and ignored/suppressed counts remain
visible. Set `RUST_LOG=debug` inside the PHP container when troubleshooting
zizmor logging. The offline workflow-output suite covers these stages and
failure propagation without downloading or executing the actual validators.

The quality-output regression suite uses temporary fixtures to check every
migrated recipe, runner selection, report arguments, annotations, preparation
failures, and mixed goals without running real analyzers or application tests.
To exercise real quality recipes from the repository root on the Docker host
with development services running:

```bash
make lint-yaml
make composer-validate
make run-tests-php
```

The application-test recipe retains its existing test-cache cleanup and
regenerates `build/logs/clover.xml` and `build/logs/junit.xml`.

Setup and mutation targets also use completion-only status messages:
`install`, `install-tools`, `update`, `update-tools`, `cache-clear`,
`cache-warmup`, `local-configuration`, `configure-ci`, `generate-jwt-keys`,
`generate-ssl-cert`, `clear`, `clear-tools`, and `phpmetrics`.
Yellow warnings describe what will change before cleanup, overwrites, or
dependency operations. `ecs-fix` and `composer-normalize` also warn before
rewriting files. These warnings do not introduce new confirmation prompts;
existing Composer hooks and OpenSSL questions remain unchanged.

Configuration creation warns before replacing local/CI environment files;
JWT regeneration warns that existing tokens may become invalid. Warnings
never display configuration contents or key passphrases. Certificate
generation stops immediately on an OpenSSL failure instead of continuing
through later steps.

`clear-tools` deletes only `vendor` directories beneath `tools`, handles
spaces in paths, and does not traverse into vendor directories before deleting
them. Both dependency cleanup targets require dependencies to be reinstalled.
When `bin/console` is absent, `cache-clear` retains filesystem cleanup and
`cache-warmup` ends with `[SKIP]`, not a false success.

PhpMetrics checks `build/logs/junit.xml` and names that same file in its
prerequisite warning. If it is missing, the existing automatic application-test
run is retained, but failure stops report generation. A completed test run must
actually create `junit.xml` before PhpMetrics starts. Existing HTML report files
in `build/phpmetrics` may be overwritten.

Run setup/mutation regression checks from the repository root on the Docker
host with development services running:

```bash
docker compose exec -T php python3 scripts/tests/make-quality-output-test.py
make test-make-routing
```

These tests use isolated fixtures for installs, updates, configuration copying,
key/certificate generation, and failure handling. They do not update your
dependencies or replace your keys. Cleanup checks delete only temporary fixture
directories, not repository dependencies.

`make help` groups the documented target inventory by purpose, with host-only
Docker groups identified explicitly. `[DESTRUCTIVE]` marks resource deletion
or shutdown (including cache cleanup); `[OVERWRITE]` marks configuration, key,
source-fixing, report-copying, and image-tag replacement commands. The list is
read from Makefile descriptions and shared target metadata, not a separate
help-only inventory.
Help respects `COLOR` and `NO_COLOR` and remains usable without Docker.
Use `GROUP` to show only one purpose; omitted or `GROUP=all` shows the full
inventory. Unknown groups fail with the supported choices instead of an empty
successful listing. Filtering changes only help output, not command routing.

From the repository root on the Docker host (no running services required):

```bash
bash scripts/make.sh help
bash scripts/make.sh help GROUP=quality
bash scripts/make.sh help GROUP=docker COLOR=never
```

Available groups, in display order, are `information`, `tests`, `quality`,
`checks`, `reports`, `setup`, `lifecycle`, and `docker`. Direct script callers
can use `bash scripts/make-help.sh quality` from the same working directory.

Information commands keep their native stdout: configuration JSON, statistics
tables, license counts, application/version information, and digest/platform
details. Shared status and report-path messages go to stderr, so redirects do
not acquire success banners. License and statistics pipelines propagate failed
commands instead of reporting success based only on their final pipeline stage.
The existing configuration command still intentionally displays configuration;
added messages never repeat its values.
License counts are extracted from Composer JSON rather than its human-readable
table, excluding headers and version columns. Alternative licenses are joined
with `OR`; empty license lists are labeled `UNSPECIFIED`.

Successful report generators identify `build/logs/clover.xml`,
`build/logs/junit.xml`, `build/phpmetrics`, `build/phploc.json`, and
`build/psalm.json` as applicable. Infection identifies `build/infection`;
its native output owns the exact generated file inventory.
`scripts/report-output.sh` verifies every declared local report is a nonempty
file before printing its `[INFO]` path message. Test XML, Psalm/PHPLOC JSON,
PhpMetrics `index.html`, and prepared Sonar copies use this shared check.
Missing or empty outputs fail the target even if the generator returned zero.
This checks file availability, not schema validity or freshness of existing
reports. Infection names its report directory only; native output owns which
files its configured reporters actually generated.

Psalm's local JSON report message does not claim Shepherd upload success.
The installed Shepherd plugin can skip submission or print HTTP/cURL errors
without failing analysis. `psalm-shepherd` and `psalm-github` therefore finish
with analysis/local-report completion and explicit unverified upload status.
Native upload diagnostics remain visible and the existing exit policy is
unchanged; a successful analysis does not imply a successful submission.
`report-coveralls` retains verbose client output, including its configured
JSON payload path and HTTP submission status. A generated payload alone does
not produce `[OK]` if the client returns an upload failure. Its completion
message confirms the client finished and directs the reader to native HTTP
status for upload confirmation, rather than inferring success from JSON creation
or dry-run completion. No payload is dumped by the shared output helpers.

`prepare-sonar-reports` checks both nonempty source reports first, prepares
relative-path copies in temporary files, then replaces the local copies.
It names `build/sonar/clover.xml` and `build/sonar/junit.xml` and explicitly
states that no upload occurred. It does not alter the source reports.
The host-side Sonar action performs upload separately.

Run the information/reporting regression checks from the repository root on
the Docker host with the PHP development service already running:

```bash
docker compose exec -T php python3 scripts/tests/make-information-output-test.py
docker compose exec -T php python3 scripts/tests/make-quality-output-test.py
```

These checks use temporary report fixtures and mocked generators/uploaders;
they never contact Coveralls or Sonar. Read-only help needs no running services:

```bash
make help COLOR=never
```

Docker lifecycle commands use blue `[RUN]` messages and report completion only
after Compose exits successfully. Development `daemon*` startup does not wait
for readiness. `daemon-prod` retains `--wait` and reports only Compose's
running/health checks, not HTTP verification. `ci-start` still starts only PHP
with initialization disabled; it does not prove application readiness.
Foreground `start*` completion means the attached session ended, not that the
application is healthy. Exit 130/143 is a yellow cancellation with a nonzero
status, not green success or a red execution failure.

`bash`, `fish`, and `logs` print `[RUN]` before directly executing Compose,
without wrapping or redirecting their interactive session or streaming output,
and without a final success banner. Host-only targets fail explicitly when
called inside a container. Startup rejects already-running services in this
Compose project, while
unavailable PHP shell contexts fail explicitly. Logs remain accessible when
the selected services have stopped.

Docker builds and QEMU registration warn before mutation and report completion
only after Docker succeeds. Existing confirmations remain mandatory for QEMU
and registry pushes; declining prints yellow `[SKIP]` and retains exit 0.
Build success does not prove runtime behavior.

Host-wide cleanup helpers warn that they affect unrelated projects too. Prune
retains its existing `y` confirmation; declining or reaching end-of-input skips
without changes. Empty resource lists skip kill/remove operations rather than
invoking Docker without IDs. Listing and mutation errors remain visible and
stop further operations; prune never follows a failed container kill.
No maintenance command is restricted to this Compose project.

Run offline lifecycle regression tests from the repository root on the Docker
host, with the PHP development service already running:

```bash
docker compose exec -T php python3 scripts/tests/make-lifecycle-output-test.py
make test-make-routing
make test-docker-images
```

The lifecycle fixtures mock Docker, including confirmations and failures.
They do not build images, start/stop services, register emulators, publish
registry tags, or prune real Docker resources.

Update and security checks distinguish findings from execution failures:

* `check-action-updates` shows yellow updates and red discovery/lookup failures.
  Its direct checker returns 0 for current pins, 1 for updates, and 2 for failed
  discovery or lookups. The informational Make target still accepts 1 and 2,
  explicitly reporting that policy rather than claiming a clean result.
* `check-dependencies-*` keeps its existing informational success status for
  available updates, with yellow new-version values and a warning summary.
  A failed Composer invocation still fails the command and Make.
* `docker-image-check-updates` keeps exit 1 for updates **or** failed checks.
  Its yellow summary means updates only; its red summary separately counts
  failed checks and updates. Skipped versions remain yellow and return success.
  `docker-image-verify-pins` remains blocking for invalid/unresolvable pins.
* `check-security` remains blocking for advisory-constraint, cache-refresh,
  or installed-package audit failures. If the optional local checker is absent,
  a yellow `[SKIP]` replaces its previous silent omission; exit success is
  preserved, but it does not claim that all security checks ran.

### Output contract regressions [ᐞ](#table-of-contents)

<a id="output-contract-regressions"></a>

Run the consolidated offline suite from the repository root on the Docker
host, with the PHP development service already running:

```bash
make test-command-output
make test-command-output COLOR=always
make test-command-output COLOR=never
make test-command-output COLOR=always NO_COLOR=1
```

Host invocations forward into the PHP development container, including when
`GITHUB_WORKFLOW` selects the CI Compose override. Inside that container,
the same `make test-command-output` command runs directly. Container availability
is required; this target never falls back to host Python or other project tools.

The runner executes nine suites: shared statuses and expected-negative Docker
scenarios, Make filtering/shell integration, blocking checks, quality/mutation
recipes, lifecycle commands, information/reports, check outcome policies,
workflow output, and host/CI forwarding. It stops on the first failed suite
and prints the aggregate `[OK]` only after all suites succeed.
The Docker scenario suite is exercised by the shared-status tests.
All Docker, registry, configuration, dependency, key, and upload mutations use
isolated fixtures or mocks. This does not prove live service health, registry
availability, successful uploads, or real application-test report generation.

Coverage includes forced/plain/auto color, `NO_COLOR`, stdout/stderr preservation,
missing executables, blocking failures, explicit skips, interruptions,
expected-negative assertions, CI environment forwarding, dry runs, and multiple
Make goals. Missing tools and blocking checks remain nonzero. An expected-negative
scenario reports `[OK]` only after verifying the intended failure and diagnostics.
Successful earlier stages may remain visible before a later failure; no failed
stage or aggregate reports success.

Without `--keep-going`, Make stops before later goals after a blocking failure.
With `--keep-going`, an independent later target may succeed and print its own
`[OK]`; the invocation still exits nonzero. The informational action-update
wrapper is the established exception: direct checker exits 1/2 produce
`[WARN]`/`[FAIL]` while its Make target deliberately returns success.

Color controls apply to shared statuses, not every native tool's formatting.
`COLOR=auto` enables color only on a suitable stderr terminal; host forwarding
resolves this before `exec -T`. `COLOR=always` forces color, `COLOR=never`
disables it, and any nonempty `NO_COLOR` takes precedence. `TERM=dumb`
disables automatic color. Labels remain meaningful without ANSI escapes.

If a suite fails, retain its native diagnostics and run that focused script
inside the PHP development container. For example, from the repository root
on the Docker host with the development service running:

```bash
docker compose exec -T php python3 scripts/tests/make-quality-output-test.py
```

For filtered GNU Make recipe error lines, use the existing launcher from the
same host directory:

```bash
bash scripts/make.sh test-command-output
```

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

Run from the repository root **inside the PHP development container**, not on
the host. Open it with `make bash` or `make fish`, or use an IDE Dev Container
terminal. Python automatically uses `/home/dev/.venv`.
See [Python tooling](../.devcontainer/README.md#python-tooling) for details.

```bash
python3 scripts/check-skills.py
```

Alternatively, use `make check-skills` from either the host or the container;
the target always runs the checker inside Docker.

This standard-library Python check also runs via `make check-skills` in the
documentation CI job, using the existing Compose `php` development service
rather than running Python directly on the runner. It
validates local file links and Markdown anchors across repository Markdown
files, in addition to checking skill metadata and index coverage.

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

### GitHub workflow validator [ᐞ](#table-of-contents)

<a id="github-workflow-validator"></a>

File: `scripts/lint-workflows.sh`

```bash
make lint-workflows
```

The target runs in the PHP development container locally and directly on the
GitHub runner in CI. It downloads actionlint 1.7.12, ShellCheck 0.11.0,
action-validator 0.9.0, and zizmor 1.31.1 into a temporary directory,
verifies their pinned SHA-256 checksums before execution, and removes them
on exit. Linux AMD64 and ARM64 are supported; downloads require network
access, `curl`, `tar`, and `sha256sum`. Download or checksum failures fail
the check.

Release tags are declared once with a `tool-pin: owner/repo@COMMIT_SHA`
comment recording the verified release commit. Binary SHA-256 checksums remain
the execution integrity pins; a source commit SHA is not a binary checksum.
The existing update checker inventories these declarations and checks for
newer stable tags, verifying current tag/commit parity (including annotated
tags). Run all action/tool checks with `make check-action-updates`, or only
these tools inside the development container:

```bash
bash scripts/check-action-updates.sh --tools-only
python3 scripts/tests/check-workflow-tool-updates-test.py
```

Tool-only check exit codes are `0` for verified current pins, `1` for updates,
and `2` for missing/malformed pins, tag mismatches, or upstream lookup errors.
Candidates may cross major versions; review compatibility and verify every
platform's release-asset checksum before updating. Validation never updates
tools automatically. Tool update lookups are separate from `lint-workflows`.

Actionlint validates every `.yml` and `.yaml` workflow, including YAML anchors,
aliases, expressions, and job dependencies, with ShellCheck enabled for
supported workflow shell steps. Action-validator separately checks every
local `action.yml` and `action.yaml` against its bundled metadata schema.
Composite-action shell bodies are not checked by these metadata checks.
Zizmor runs offline with `--min-severity low` and the `.github/zizmor.yml`
configuration. It audits workflows and local actions for template injection,
credential persistence, dangerous environment-file writes, and excessive
permissions. A deliberate exception needs an inline
`# zizmor: ignore[rule]` comment with the reason.
`.github/zizmor.yml` holds rule-wide settings; it currently disables only the
`self-repository` rule, because local actions use `./` rather than `$/`.
Disable or configure a rule there only with a comment explaining why.
ShellCheck also checks the validator script itself.

The `Validate GitHub workflows` job is a prerequisite of `ci-summary`; failures,
cancellations, or skipped validation block tests and Docker builds.
`make lint-yaml` remains the separate Symfony configuration check. Static
validation does not replace testing actual GitHub workflow execution.

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

Run the helper's regression tests without registry access from the repository
root, either on the host or inside the development container. Make runs the
tests inside the PHP container with mocked Docker and registry commands:

```bash
make test-docker-images
```

The root `Dockerfile` defaults to the `production` target. Use
`TARGET=development` to build the development image instead. Dockerfiles
with an unnamed final stage do not prompt for a target and use Docker's final
stage by default. Use `TARGET` explicitly to select a named intermediate stage.

See [Docker multi-platform images](../doc/DOCKER_MULTI_PLATFORM.md) for
details.

---

[Back to previous](../README.md)
