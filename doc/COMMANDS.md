# What is this?

<a id="what-is-this"></a>

This document contains all custom commands that you can use within this
application during development stage.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Commands](#commands)
      * [Makefile](#makefile)
      * [Symfony Flex recipes](#symfony-flex-recipes)
      * [Symfony console](#symfony-console)
      * [Custom commands](#custom-commands)
        * [check-dependencies](#check-dependencies)
        * [user:management](#usermanagement)
        * [api-key:management](#api-keymanagement)
        * [utils:create-date-dimension-entities](#utilscreate-date-dimension-entities)

## Commands [ᐞ](#table-of-contents)

<a id="commands"></a>

From the repository root on the Docker host, start development in the
foreground to see live container logs:

```bash
make start
```

Alternatively, start in the background without streaming container logs:

```bash
make daemon
```

After `make daemon`, use this host command to follow the logs:

```bash
make logs
```

Run project commands inside the PHP development container, or use Make targets
from the host to forward them automatically. Docker lifecycle and
image-management commands run on the host.

List commands grouped by purpose from the repository root on the Docker host:

```bash
make help
```

Everyday application groups appear before setup and Docker maintenance.
Within each group, routine commands precede overwrite and destructive commands,
with alphabetical target names at each risk level. Filter from the same host
working directory without requiring running containers:

```bash
bash scripts/make.sh help GROUP=quality
bash scripts/make.sh help GROUP=docker COLOR=never
```

`GROUP=all` is the default; other groups are `information`, `tests`, `quality`,
`checks`, `reports`, `setup`, `lifecycle`, and `docker`. Invalid groups fail
explicitly and list supported choices.

Help marks destructive and overwrite targets. Information commands keep native
stdout, with status messages on stderr. For example, from the repository root
on the Docker host with development services running, redirect the license
summary without including status banners:

```bash
mkdir -p build
make check-licenses > build/licenses.txt
```

Target groups, risk markers, execution routing, and wrapped labels share the
Makefile's `REGISTER_TARGET` metadata. Recipe descriptions remain beside their
commands; help no longer guesses categories or risk from target names.

Verify command output contracts from the repository root on the Docker host
with development services already running:

```bash
make test-command-output
make test-command-output COLOR=always NO_COLOR=1
```

This offline target covers colors, stream preservation, failures, cancellation,
expected-negative cases, multi-target invocations, and host/CI forwarding.
`COLOR=auto|always|never` controls shared statuses and native tool color flags;
nonempty `NO_COLOR` overrides color, including `COLOR=always`. GitHub analyzer
formats stay plain regardless of status colors. ECS, PhpMetrics, and the JSON
configuration check use a streaming SGR-only filter in plain mode where native
disable flags are unavailable or unreliable. Tools without force-color support
retain their native detection. Blocking failures stay nonzero.
Compose receives the resolved color choice via `COMPOSE_ANSI`.
See [the regression guide](../scripts/README.md#output-contract-regressions)
for individual suites and the informational action-update exception.

Report generators identify their output paths after successful execution.
`prepare-sonar-reports` creates relative-path copies at
`build/sonar/clover.xml` and `build/sonar/junit.xml`; it requires existing
nonempty test reports and does not upload anything. `report-coveralls` runs
the uploader separately: its verbose output distinguishes JSON payload
creation from the HTTP submission result. Local Psalm JSON generation likewise
does not imply Shepherd upload success.

For direct commands, open a Bash shell inside the PHP container from the host:

```bash
make bash
```

Or open a Fish shell instead:

```bash
make fish
```

Both shells run inside the `php` service as the `dev` user. Compose maps this
user's UID and GID to your host user to avoid file-permission conflicts.

Both entry points enable filtered Make output after normal user configuration.
For an existing Bash terminal on the host or inside the PHP development
container, enable filtering from the repository root:

```bash
source scripts/make-shell.sh
```

For an existing Fish terminal in either context, use:

```fish
source scripts/make-shell.fish
```

Alternatively, invoke `bash scripts/make.sh <target>` without modifying the
current shell. These entry points suppress redundant GNU Make recipe-error
lines, not native diagnostics or useful Make setup errors, and retain failure
exit statuses. Raw `command make` can still print its own outer error line.

Before running any host-only command, leave the container shell:

```bash
exit
```

Host-only Make targets are `bash`, `fish`, `logs`, `start`, `start-build`,
`start-prod`, `daemon`, `daemon-build`, `daemon-prod`, `stop`, `ci-start`,
`ci-stop`, and all `docker-*` targets. Do not run these from an IDE Dev
Container terminal either; use a separate host terminal.

Lifecycle commands report completion only after Compose succeeds. Development
`daemon` and `daemon-build` mean containers were started, not that the
application is ready. `daemon-prod` waits for Compose running/health checks but
does not verify HTTP. Foreground `start*` completion means the attached session
ended; interruption is reported as cancellation. Startup warnings describe
entrypoint dependency installation, key generation, and migrations.

Shells and streaming logs remain attached directly, with a `[RUN]` message and
no premature or final success banner. The shared command helper's `--stream`
mode executes these sessions in place, preserving terminal descriptors and
native exit/signal behavior. Its `--run` mode handles finite commands, complete
multi-step recipes, and information commands; statuses stay on stderr so
structured stdout remains usable. Foreground `start*` uses `--run` to retain
session-ended and cancellation reporting.
Invalid host/container contexts and
rejected startup attempts return failure. `stop` and `ci-stop` remove containers
and networks while retaining named volumes.

Shells and forwarded commands check only the PHP service, without requiring
optional development services. A running but unhealthy/starting PHP service
produces a warning and remains accessible for troubleshooting; a stopped or
absent service blocks execution. Logs can be followed for existing stopped
PHP/nginx/MariaDB containers. Startup checks only this Compose project's running
services, not globally matching container names. Help and dry runs do not query
Docker service state.

Docker maintenance helpers affect all host resources, not just this project.
Warnings precede destructive operations; the existing prune confirmation is
unchanged. Empty kill/remove selections and declined confirmations report
`[SKIP]`. Build/QEMU helpers preserve their existing inputs and confirmations,
with success reported only after Docker completes.

CI uses the same development image via `make ci-start`, project Make commands,
and `make ci-stop`. The CI override bypasses application startup and keeps a
command container running. It shares `/app` with the checkout so reports remain
available to host-side artifact actions. The test job passes its database URL
into the container using `host.docker.internal` and the runner's assigned port.
Do not run `make configure-ci` locally: it is restricted to GitHub Actions and
replaces CI environment files and JWT keys.

The following block runs entirely on the **GitHub Actions runner host**, from
the checkout root, not in a container shell. The reusable bootstrap starts
the PHP container; subsequent project targets forward into it, while
`make ci-stop` runs on the host:

```bash
bash scripts/make.sh ci-start
bash scripts/make.sh install
bash scripts/make.sh tool-versions
bash scripts/make.sh composer-validate
bash scripts/make.sh check-skills
bash scripts/make.sh ci-stop
```

CI uses the launcher explicitly to filter both host and nested container Make
errors. In workflows, put `bash scripts/make.sh ci-stop` in an `always()` step
so failed checks
do not skip cleanup. Keep CI setup separate from a running local development
stack; both use the same Compose PHP service.

The host Make targets pass your UID and GID to Compose. For IDE Dev Container
UID/GID configuration, see [UID/GID mapping](../.devcontainer/README.md#uidgid-mapping).

For a targeted test, run `phpunit <test-file-or-directory>` from the repository
root inside the PHP development container; no explicit runner path is needed.
For example:

```bash
phpunit tests/Unit/Rest/SearchTermTest.php
```

Use `make run-tests` for the full suite. See [the testing guide](TESTING.md) for
details.

### Makefile [ᐞ](#table-of-contents)

<a id="makefile"></a>

Symfony Flex comes with `Makefile` configuration so that you can easily run
some generic commands via `make` command. Below is a list of currently
supported (main commands) make commands, note that you can get this same list
with just running `make` command. This is a command inventory, not a runnable
shell block. Use the host-only classification above; other project targets
run inside PHP, either directly or through automatic host forwarding:

```text
bash                        # Get bash inside PHP container
application-info            # Display Symfony application information
cache-clear                 # Clears the cache
cache-warmup                # Warms up an empty cache
check-dependencies-latest   # Checks if any vendor dependency can be updated
                            # (latest versions)
check-dependencies-minor    # Checks if any vendor dependency can be updated
                            # (only minor versions)
check-dependencies-patch    # Checks if any vendor dependency can be updated
                            # (only patch versions)
check-licenses              # Check vendor licenses
check-security              # Checks that application doesn't have installed
                            # dependencies with known security vulnerabilities
check-action-updates        # Check pinned GitHub Actions and available updates
check-skills                # Validate skills and Markdown links inside Docker
check-requirements          # Check application runtime requirements
ci-start                    # Start development command container (CI host)
ci-stop                     # Stop CI containers (CI host)
clear                       # Clear _all_ vendor and tool dependencies
clear-tools                 # Clears all tools dependencies
composer-normalize          # Normalizes `composer.json` file content
composer-require-checker    # Check the defined dependencies against your code
composer-unused             # Show unused packages by scanning and comparing
                            # package namespaces against your source
composer-validate           # Validate `composer.json` file content
configuration               # Prints out application current configuration
configure-ci                # Configure environment files and JWT keys (CI only)
daemon                      # Start application in development mode in
                            # background
daemon-build                # Build containers and start application in
                            # development mode in background
daemon-prod                 # Build and start local production in background
docker-build-multi-platform # Build and push multi-platform image (asks
                            # PLATFORMS, DOCKERFILE, TARGET, TAG, BUILDER)
docker-build-platform       # Build image for single platform (asks
                            # PLATFORM, DOCKERFILE, TARGET, TAG)
docker-builder-platforms    # Show platforms supported by current buildx
                            # builder
docker-image-check-updates  # Report newer stable Docker Hub image versions
                            # (optional IMAGE; otherwise external Dockerfile images)
docker-image-digest         # Show multi-platform index digest for image
                            # (asks IMAGE)
docker-image-verify-pins    # Verify that PHP, nginx and MariaDB base
                            # images are multi-platform
docker-kill-containers      # Kill all running docker containers
docker-prune                # Kill all running containers and prune all docker
                            # stuff
docker-remove-containers    # Remove all docker containers
docker-remove-images        # Remove all docker images
docker-setup-qemu           # Install QEMU emulators for cross-platform
                            # builds (asks PLATFORMS)
ecs-fix                     # Runs The Easiest Way to Use Any Coding Standard
                            # to fix issues
ecs                         # Runs The Easiest Way to Use Any Coding Standard
fish                        # Get fish inside PHP container
generate-jwt-keys           # Generates JWT auth keys
generate-ssl-cert           # Generate self signed SSL certificates
infection                   # Runs Infection to codebase
install-tools               # Install composer tools dependencies
install                     # Install project Composer dependencies
lint-configuration          # Lint current defined `application.json` that it
                            # contains valid JSON
 lint-markdown              # Lint Markdown files
lint-workflows              # Validate GitHub workflows, shell, and action metadata
lint-yaml                   # Lint config YAML files
local-configuration         # Create local configuration files
logs                        # Show logs from all containers
phpcs                       # Runs PHP CodeSniffer
phpinsights                 # Runs PHP Insights
phplint                     # Runs PHPLint
phploc                      # Runs `phploc` and create json output
phpmetrics                  # Generates PhpMetrics static analysis
php-parallel-lint           # Lint PHP files with `php-parallel-lint`
phpstan-github              # Runs PHPStan static analysis tool (GitHub)
phpstan                     # Runs PHPStan static analysis tool
prepare-sonar-reports       # Prepare relative report paths for host-side Sonar
project-stats               # Create simple project stats
psalm                       # Runs Psalm static analysis tool
psalm-github                # Runs Psalm static analysis tool (GitHub)
psalm-shepherd              # Runs Psalm static analysis tool + report results
                            # to shepherd
report-coveralls            # Upload coverage to Coveralls
run-tests-phpdbg            # Runs all tests via phpunit (phpdbg)
run-tests-php               # Runs all tests via phpunit (pure PHP)
run-tests                   # Runs all tests via phpunit (Uses phpdbg if that
                            # is installed)
start                       # Start application in development mode + watch
                            # output
start-build                 # Build containers and start application in
                            # development mode + watch output
start-prod                  # Build and start local production + watch output
stop                        # Stop application containers
test-make-routing           # Test local and CI Make command forwarding
test-docker-images          # Test Docker helpers inside the development container
tool-versions               # Display PHP project tool versions
update                      # Update composer dependencies
update-tools                # Update composer tools dependencies
```

`check-skills`, `lint-workflows`, `check-requirements`, `test-make-routing`,
and `docker-image-verify-pins` show blue `[RUN]` status messages followed by
green `[OK]` on success or red `[FAIL]` with the command's exit code on failure.
Native diagnostics remain visible and failures still fail Make.
The same conventions apply to application tests, mutation tests, lint and
static-analysis targets, and Composer validation/checkers. Their summaries
cover the entire recipe, including preparation, and retain native progress,
GitHub annotations, and report paths. `run-tests` still selects PHP/phpdbg
automatically and prints only one final status.
Setup, dependency mutation, cache, configuration, key/certificate generation,
cleanup, and PhpMetrics targets likewise report success only after completion.
Yellow warnings describe overwrites and deletions before they happen, without
adding new confirmation prompts. PhpMetrics requires `build/logs/junit.xml`,
automatically runs tests if it is missing, and stops if that prerequisite fails.
Update-check colors distinguish available updates (yellow) from failed checks
(red). Existing exit policies are preserved, including the informational
action-update Make wrapper. See the scripts guide for each target's outcome
policy and explicitly skipped optional security checks.
See [command status output](../scripts/README.md#command-status-output) for
color controls, regression checks, and offline versus live-check behavior.

To hide GNU Make's redundant recipe error-location lines without hiding
command diagnostics or changing the failure exit status, use the optional
launcher from the repository root on the Docker host with services running
or inside the PHP development container:

```bash
bash scripts/make.sh check-skills
```

Direct `make` continues to print its own recipe error lines unless the shell
integration is active. Shells opened with `make bash` load that integration
automatically, preserving the user's normal `~/.bashrc`.

For an opt-in Bash session integration, run from the repository root on the
Docker host with services running or inside the PHP development container:

```bash
source scripts/make-shell.sh
make check-skills
```

The function filters Make output only in this repository's root. Disable it
with `unset -f make`, or bypass it with `command make check-skills`.

Check pinned GitHub Actions and available updates from the repository root
on the host or inside the PHP container:

```bash
make check-action-updates
```

`make lint-workflows` runs actionlint (with ShellCheck), action-validator and
zizmor. Zizmor audits workflows for template injection, credential
persistence and excessive permissions using
[`.github/zizmor.yml`](../.github/zizmor.yml). Fix findings where possible.
For a deliberate exception, add an inline comment on the reported line with
the reason:

```yaml
run: echo "$VALUE" >> "$GITHUB_ENV" # zizmor: ignore[github-env] reason
```

Rule-wide changes belong in `.github/zizmor.yml`; see
[`scripts/README.md`](../scripts/README.md) for the full validator behavior.

To print current GitHub Action pins as Markdown, first open a container shell
from the host:

```bash
make bash
```

Then run inside the container, from `/app`:

```bash
bash scripts/check-action-updates.sh --current-pins-md
```

To rebuild and start development, use a **host terminal**, not the container
shell above. Stop the running stack first (`make daemon-build` requires it):

```bash
make stop
make daemon-build
```

### Symfony Flex recipes [ᐞ](#table-of-contents)

<a id="symfony-flex-recipes"></a>

Run these Composer commands from the repository root inside the PHP development
container (`make bash` from the host):

```bash
composer recipes --help
composer recipes                         # List recipes and update availability
composer recipes --outdated              # List outdated recipes only
composer recipes vendor/package          # Inspect files, history, and update instructions
composer recipes:update --help
composer recipes:update vendor/package   # Apply one recipe update
```

Recipe updates change application scaffolding and `symfony.lock`, separately
from Composer package upgrades. The updater requires a clean tracked working
tree and index, applies patches, and automatically stages changes without
committing them. Inspect untracked files for collisions as well. Do not discard
or stash existing work merely to satisfy the clean-tree requirement.

Review `git status`, `git diff --cached`, and `git diff` after updating. A
successful command can still report conflicts or skipped files; resolve and
validate these before considering the update complete. Do not substitute a
forced `recipes:install` for an update without reviewing its overwrite risk.

Follow the [Symfony Flex recipe skill](../.github/skills/update-symfony-recipes/SKILL.md)
for the detailed workflow, preservation of project customizations, and
change-specific validation.

### Symfony console [ᐞ](#table-of-contents)

<a id="symfony-console"></a>

Run the examples in this section and all custom-command sections below
**inside the PHP development container**, from `/app`. Open a shell using
`make bash` or `make fish` from the host, or use an IDE Dev Container terminal.

List all Symfony console commands:

```bash
./bin/console
```

or

```bash
console
```

### Custom commands [ᐞ](#table-of-contents)

<a id="custom-commands"></a>

Project contains following custom console commands to help eg. user management:

```bash
./bin/console check-dependencies                    # To check dependency updates
./bin/console user:management                       # To manage your users and
                                                    # user groups
./bin/console api-key:management                    # To manage your API keys
./bin/console utils:create-date-dimension-entities  # Console command to create
                                                    # 'DateDimension' entities.
```

#### check-dependencies [ᐞ](#table-of-contents)

<a id="check-dependencies"></a>

Checks for dependency updates in the root project and tool dependency groups.
The progress bar shows the group currently being checked and a completion
message when all groups have finished.

Use `--level=latest`, `--level=minor`, or `--level=patch` to select the update
level. The default is `latest`.

Each dependency group has a 60-second timeout. Use `--timeout=120` to allow
120 seconds per group. The timeout must be a positive, finite number of seconds;
decimal values are supported.

```bash
./bin/console check-dependencies                             # Latest updates
./bin/console check-dependencies --level=minor               # Minor updates
./bin/console check-dependencies --level=patch               # Patch updates
./bin/console check-dependencies --level=patch --timeout=120 # Custom timeout
```

The corresponding container-aware Makefile targets are
`make check-dependencies-latest`, `make check-dependencies-minor`, and
`make check-dependencies-patch`.

#### user:management [ᐞ](#table-of-contents)

<a id="usermanagement"></a>

This command is just a wrapper for following commands:

```bash
./bin/console user:create         # To create user
./bin/console user:create-group   # To create user group
./bin/console user:create-roles   # To initialize user group roles
./bin/console user:edit           # To edit user
./bin/console user:edit-group     # To edit user group
./bin/console user:remove         # To remove user
./bin/console user:remove-group   # To remove user group
./bin/console user:list           # To list current users
./bin/console user:list-groups    # To list current user groups
```

#### api-key:management [ᐞ](#table-of-contents)

<a id="api-keymanagement"></a>

This command is just a wrapper for following commands:

```bash
./bin/console api-key:create          # To create API key
./bin/console api-key:edit            # To edit API key
./bin/console api-key:change-token    # To change API key token
./bin/console api-key:remove          # To remove API key
./bin/console api-key:list            # To list API keys
```

#### utils:create-date-dimension-entities [ᐞ](#table-of-contents)

<a id="utilscreate-date-dimension-entities"></a>

Command to create `DateDimension` entities that can be used with date/time
related report queries.

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
