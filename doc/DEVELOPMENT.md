# What is this?

<a id="what-is-this"></a>

This document contains basic information how you _should_ development this
application.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Development](#development)
      * [Branch names and pull request titles](#branch-names-and-pull-request-titles)
      * [Composer PHP platform](#composer-php-platform)
      * [Native PHP function calls](#native-php-function-calls)
      * [IDE](#ide)
      * [PHP Code Sniffer](#php-code-sniffer)
      * [Database changes](#database-changes)
      * [Continuous integration](#continuous-integration)
        * [Reproducing CI jobs locally](#reproducing-ci-jobs-locally)
        * [Job timeouts](#job-timeouts)
        * [Runner hardening plan](#runner-hardening-plan)
        * [Image signing and SBOM](#image-signing-and-sbom)

## Development [ᐞ](#table-of-contents)

<a id="development"></a>

The repository uses PSR-12 as its coding-style baseline, with additional
project-specific rules configured in [`ecs.php`](../ecs.php). Run `make ecs` to
check the code style or `make ecs-fix` to apply fixes. PHP-FIG's newer
[PER Coding Style Guide](https://www.php-fig.org/per/coding-style/) extends and
replaces PSR-12, but has not been adopted as this repository's baseline. Follow
the repository configuration where external guidance differs.

The repository does not use Yoda-style conditions: put the variable or
expression before the compared value (for example, `$status === 'active'`).

* [PSR-1: Basic Coding Standard](https://www.php-fig.org/psr/psr-1/)
* [PSR-12: Extended Coding Style Guide](https://www.php-fig.org/psr/psr-12/)
* [Symfony Coding
  Standards](https://symfony.com/doc/current/contributing/code/standards.html)

### Branch names and pull request titles [ᐞ](#table-of-contents)

<a id="branch-names-and-pull-request-titles"></a>

Recent merged pull requests commonly use a type and scope to make the change
easy to scan. Use the following conventions for new changes, including AI
assisted work:

* Name a branch `<type>(<scope>)/<short-kebab-case-description>`, for example
  `feat(console)/check-dependencies-improvements` or
  `chore(ai)/link-shared-validation-to-agents`,
  `chore(env)/nginx-update`, `chore(ci)/dependabot-configuration`, or
  `fix(api)/correct-security-annotation`. For documentation-only maintenance,
  use `chore(docs)/branch-and-pr-naming`.
* Name a pull request `<Type>(<scope>) - <concise change summary>`, for example
  `Feat(console) - Improve the check-dependencies command` or
  `Fix(api) - Correct the security annotation` or
  `Chore(docs) - Document branch and pull request naming`.
* Use a short, lowercase type and scope in branch names. In pull request
  titles, capitalize the type and keep the scope lowercase. Common types in
  this repository include `feat`, `fix`, `chore`, and `docs`; choose a scope
  that identifies the affected area, such as `console`, `ci`, `env`, `ai`, or
  `docs`. Always use `chore(docs)` for documentation-only changes, in both
  the branch (`chore(docs)/...`) and the pull request title
  (`Chore(docs) - ...`); never use `docs/...` as a branch prefix. When only
  AI guidance changes (`AGENTS.md`, `CLAUDE.md`, `.github/skills/`,
  `doc/AI_RULES.md`), use `chore(ai)/...` and `Chore(ai) - ...`. Use a type
  such as `feat` or `fix` when documentation accompanies that functional
  change.
* Keep the description specific to the change. Use the same type and scope in
  the branch and title, and keep their change summaries aligned. The formats
  are intentionally different; for AI-assisted work, decide the proposed PR
  title first and derive the task branch from it. Target `master` unless the
  work has a specific reason to target another branch.
* A pull request whose base is not `master` is a stacked pull request: its
  branch is based on the parent pull request's branch. State the base branch
  and the parent pull request in the description. Merge the parent first,
  retarget the child to `master`, then rebase it. See
  [REQUIRED_CHECKS.md](REQUIRED_CHECKS.md#stacked-pull-requests) for the
  procedure.

### Composer PHP platform [ᐞ](#table-of-contents)

<a id="composer-php-platform"></a>

Composer resolves dependencies against `config.platform.php`, not necessarily
the installed PHP version. Keep this value and the minimum PHP requirement in
the root and `tools/*/composer.json` files aligned with the supported, patched
PHP runtime, and refresh their lockfiles after changing these settings.

If `roave/security-advisories` rejects the configured PHP version, upgrade the
runtime and Composer platform baseline rather than disabling security checks.
Rebuilding the PHP container alone does not change Composer's platform override.

### Native PHP function calls [ᐞ](#table-of-contents)

<a id="native-php-function-calls"></a>

In namespaced PHP code, explicitly import native functions with `use function`,
following the existing project style:

```php
use function array_map;
use function file_get_contents;

$values = array_map($mapper, $items);
$contents = file_get_contents($path);
```

An explicit import resolves the global function just like `\array_map(...)` or
`\file_get_contents(...)`, avoiding PHP's namespace fallback lookup. It also
allows specialized compiler opcodes where PHP supports them; not every native
function has a specialized opcode or a measurable performance improvement.
This addresses PhpStorm EA notices about qualifying native function calls.
Already imported calls do not need an additional leading backslash.

The ECS rules in [`ecs.php`](../ecs.php) qualify native function calls in
`src/` and `tests/`, then normalize them to sorted `use function` imports.
They leave classes, constants, methods, language constructs, and imported
namespaced helpers unchanged. Global-namespace code needs no qualification.
Do not edit vendor code, generated files such as `config/reference.php`, or
string callback names merely to add backslashes.

Clock and DNS functions supported by Symfony PHPUnit Bridge are excluded from
automatic native-function qualification so namespace-based mocks remain usable.
Do not explicitly import or qualify those calls when they must remain mockable.
Likewise, preserve intentional namespace overrides rather than bypassing them;
review automated changes before accepting them.

From the repository root on the Docker host with development services running,
check or apply these rules using the existing commands:

```bash
make ecs
make ecs-fix
make ecs
```

### IDE [ᐞ](#table-of-contents)

<a id="ide"></a>

I highly recommend that you use "proper"
[IDE](https://en.wikipedia.org/wiki/Integrated_development_environment)
to development your application. Below is short list of some popular IDEs that
you could use.

* [PhpStorm](https://www.jetbrains.com/phpstorm/)
* [NetBeans](https://netbeans.org/)
* [Sublime Text](https://www.sublimetext.com/)
* [Visual Studio Code](https://code.visualstudio.com/)

Just choose one which is the best for you.
Also note that project contains `.idea` folder that holds default settings for
PHPStorm.

### PHP Code Sniffer [ᐞ](#table-of-contents)

<a id="php-code-sniffer"></a>

It's highly recommended that you use this tool while doing actual development
to application. PHP Code Sniffer is added to project ```dev``` dependencies, so
all you need to do is just configure it to your favorite IDE. So the `phpcs`
command is available via following example command.

```bash
./vendor/bin/phpcs -i
```

If you're using [PhpStorm](https://www.jetbrains.com/phpstorm/) this following
link
will help you to get things rolling.

* [Using PHP Code Sniffer Tool](https://www.jetbrains.com/help/phpstorm/using-php-code-sniffer.html)

### Database changes [ᐞ](#table-of-contents)

<a id="database-changes"></a>

Migration files contain all necessary database changes
to get application running with its database structure. You can migrate
these changes to your database with following command:

```bash
./bin/console doctrine:migrations:migrate
```

After that you can start to modify or delete existing entities or create your
own ones. Easiest way to make this all work is to follow below workflow:

1. Make your changes (create, delete, modify) to entities in `/src/Entity/`
   folder
1. Run `diff` command to create new migration file
1. Run `migrate` command to make actual changes to your database
1. Run `validate` command to validate your mappings and actual database
   structure

Those commands you can run with `./bin/console doctrine:migrations:<command>`.

With this workflow you get easy approach to generic database changes on your
application. And you don't need to make any migrations files by hand (just let
Doctrine handle those). Although remember to really take a closer look of those
generated migration files to make sure that those doesn't contain anything that
you really don't want.

### Continuous integration [ᐞ](#table-of-contents)

<a id="continuous-integration"></a>

Pull request validation normally runs linting, security checks, static
analyzers, PHPUnit and both Docker image builds, with the skip rules below.
`Validate CI result` is the single required core gate, together with the
separate `dependency-review` check. The
[GitHub workflow guide](GITHUB_WORKFLOWS.md) explains events, job dependencies,
reports and troubleshooting with execution graphs.
[REQUIRED_CHECKS.md](REQUIRED_CHECKS.md) covers branch protection.

PR edits do not trigger core CI, so changing a title or body does not create
duplicate skipped checks or cancel validation. Existing validation remains
authoritative for an unchanged base and head. After changing a PR's base branch,
push a new commit or close and reopen the PR to validate against the new base
before merging. Rerunning an old run reuses its original event rather than the
new base. Other workflows keep their own event filters.

PRs, pushes and merge groups select checks using the
[CI selection table](GITHUB_WORKFLOWS.md#choosing-the-validation-path).
Markdown-only changes run documentation checks and Gitleaks; analyzer-only
changes run PHP checks without PHPUnit or image builds. Both gates accept
skips only after successful detection emits the exact category's `false`
flag. Renames include both paths. Unknown/empty comparisons, unclassified
paths, shared execution inputs and manual runs retain full validation.
Schedules run the dependency-advisory and Docker-image-update checks;
separate Dependency Review and CodeQL keep their own policies.

Docker image update availability is advisory on every event: confirmed updates
produce warnings and a maintenance summary, without failing the merge gate.
Registry lookup, update-pin validation and helper regression failures still
fail the job. Existing base-pin and vulnerability checks remain blocking.
To reproduce this policy from the repository root on the Docker host:

```bash
bash scripts/docker-images.sh check-updates --advisory
```

The local `make docker-image-check-updates` command retains its strict policy,
returning nonzero for either updates or failed checks.

CI concurrency is scoped to the workflow and Git ref, with a `-schedule`
suffix only for scheduled runs. Pushes and manual runs on `master` cannot
cancel daily checks, nor can daily checks cancel them. New runs still cancel
older runs in their own group, including superseded PR runs and overlapping
schedules. Other validation events keep their existing shared groups; PR edits
do not create runs.

PHPUnit and Docker builds start after successful change detection, in parallel
with lint, security and analyzer jobs. They no longer wait for
`Validate CI prerequisites`; this improves feedback latency but spends compute
on tests/builds even when another check later fails. The final merge gate still
requires the prerequisite gate, selected analyzers, tests, image builds and
eligible external reporting. External reporting is eligible on all PRs and
successful pushes to `master`, but requires both successful tests and the
prerequisite gate (`ci-summary`), so early test success cannot publish reports
after failed prerequisite checks. Coveralls and SonarCloud uploads require
their respective optional tokens; upload errors fail the reporting job and
workflow. Fork PRs run the job without repository secrets and skip uploads.
Test-unaffected changes skip reporting because tests and artifacts are skipped.
Schedules, manual runs and merge groups also skip reporting. Eligible reporting
must succeed for the final merge gate to pass; it does not need a separate
required-check entry.

The PHPUnit MariaDB service uses the same version and multi-platform digest as
`docker/mariadb/Dockerfile`. Update both references together when changing the
database baseline. CI uses the upstream image's
`healthcheck.sh --connect --innodb_initialized` readiness check, with a startup
grace period; its dynamically assigned port remains available to the PHP
container through `host.docker.internal`. CI does not mount the development
database volume or load its custom server configuration.

`Lightweight static analyzers` shares one bootstrap across CodeSniffer, ECS,
PHPLint, PHP Parallel Lint and PHPLoc. Each tool has its own step and runs
after earlier tool failures, provided bootstrap succeeded and the job has
not been cancelled. Failures remain blocking. Psalm, PHPStan and PHP Insights
retain separate matrix jobs; PHPLoc's artifact name and retention are unchanged.
Both CI gates require the grouped job and remaining matrix when PHP analysis
is selected. Selected analyzers and PHPUnit still run their full suites.

The shared PHP bootstrap caches Composer downloads only for dependency
installers. Jobs that defer installation (tests and external reporting) opt in
with `cache-dependencies: 'true'`; documentation and workflow checks do not
save empty caches. CI explicitly sets the container's `COMPOSER_CACHE_DIR`
to the mounted `/home/dev/.cache/composer` directory and checks the effective
Composer path and write access before installation. The `composer-downloads-v2`
key namespace excludes the old empty caches.

To check the cache boundary without application initialization, run from the
repository root on the Docker host. This starts the idle CI PHP service without
generating keys or running migrations:

```bash
make ci-start
docker compose -f compose.yaml -f docker/ci-compose.yaml exec -T php \
    bash scripts/check-composer-cache.sh
```

The check verifies configuration and write access, not GitHub cache restoration
or warm-cache downloads. Inspect cache size and Composer download logs in a
hosted run to verify reuse.

Docker builds use BuildKit's GitHub Actions v2 layer cache. Only pushes to
`master` export caches; PRs (including forks), merge groups and manual runs
only import them. Bootstrap and application-image targets have separate
scopes, so they do not overwrite each other's caches. Images are loaded into
the runner's Docker daemon for Compose and Trivy, never pushed to a registry.
Build record uploads are disabled.

Bootstrap builds `symfony-flex-backend-php:ci` with the runner's UID/GID,
then calls `make ci-start CI_PREBUILT=true`. This starts the loaded image
without rebuilding or pulling; a missing image fails instead of triggering
a second uncached build. Plain `make ci-start` still builds locally.
Python and Node installation precede the development source copy, so source
changes reuse those expensive layers.

Scheduled bootstrap and vulnerability scans pull referenced images and bypass
all layer caches, refreshing OS/tool packages. The production stage always
bypasses cache, including on PRs, so dependency installation and Composer
audit still run. Existing Trivy thresholds and merge gates are unchanged.
Cache restoration, export and fork/merge-group behavior need hosted-run
verification; local Docker caching does not prove GitHub backend access.

To build verification images without restarting services, run from the
repository root on the Docker host:

```bash
docker buildx build --load --target development \
    --tag symfony-flex-backend:verify-development .
docker buildx build --load --target production --no-cache-filter production \
    --tag symfony-flex-backend:verify-production .
```

#### Reproducing CI jobs locally [ᐞ](#table-of-contents)

<a id="reproducing-ci-jobs-locally"></a>

| CI job | Local command |
| --- | --- |
| Validate GitHub workflows | `make lint-workflows` |
| Lint documentation files | `make lint-markdown` |
| PHPUnit tests | `make run-tests-php` |
| Static analyzers | `make phpstan`, `make psalm`, `make phpcs` |
| Code style | `make ecs` |
| PHP Security Checker | `make check-security` |

Run PHP tooling inside the development container; see
[COMMANDS.md](COMMANDS.md#makefile) for all targets.

#### Job timeouts [ᐞ](#table-of-contents)

<a id="job-timeouts"></a>

Each job sets `timeout-minutes` to about twice the slowest recent successful
run, so a hung job fails fast without cutting off normal slow runs. To retune,
list recent successful runs of `main.yml`, take the longest duration per job,
double it, round up, and update the value in the workflow in the same change.

#### Runner hardening plan [ᐞ](#table-of-contents)

<a id="runner-hardening-plan"></a>

`step-security/harden-runner` runs in `audit` mode. Moving to `block` mode
with an egress allow-list is deliberately deferred until there is enough
data:

1. Open a recent run and follow the StepSecurity insights link in the
   harden-runner step summary to see the outbound endpoints per job.
2. Collect endpoints across several runs, including a `master` push and a
   scheduled run, as they differ.
3. Add them as `allowed-endpoints` per job and switch `egress-policy` to
   `block` one workflow at a time, watching for failures.

#### Image signing and SBOM [ᐞ](#table-of-contents)

<a id="image-signing-and-sbom"></a>

Image signing, provenance attestations and SBOM generation (cosign, SLSA) are
deferred because CI builds images only for verification and does not publish
them. Add them to the production image build if you start pushing images to a
registry.

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
