# What is this?

<a id="what-is-this"></a>

`CLAUDE.md` is the long-form AI assistant guide for this repository. It provides
project context, architecture expectations, workflow conventions, and practical
rules for making Symfony backend changes that stay aligned with repository
standards.

This repository is a production-ready Symfony JSON REST API backend template
with Docker setup for local development. It is designed to be consumed by
frontend applications or other backend services.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [AI documentation map](#ai-documentation-map)
    * [Version sources of truth](#version-sources-of-truth)
    * [Project Architecture](#project-architecture)
    * [Development Tools](#development-tools)
      * [Static Analysis](#static-analysis)
      * [Test runners](#test-runners)
      * [Code Quality & Analysis](#code-quality-and-analysis)
    * [Common Development Commands](#common-development-commands)
      * [Container Management](#container-management)
      * [Code Quality](#code-quality)
      * [Testing commands](#testing-commands)
      * [Database](#database)
      * [Dependencies](#dependencies)
    * [Development Workflow](#development-workflow)
      * [Adding New REST Endpoints](#adding-new-rest-endpoints)
      * [Before Committing](#before-committing)
    * [Testing](#testing)
      * [Test Structure](#test-structure)
      * [Running Tests](#running-tests)
      * [Test Environment](#test-environment)
    * [Security](#security)
      * [Authentication](#authentication)
      * [Key Security Files](#key-security-files)
      * [Security Best Practices](#security-best-practices)
    * [Configuration](#configuration)
      * [Environment Files](#environment-files)
      * [Key Configuration Files](#key-configuration-files)
      * [View Current Configuration](#view-current-configuration)
    * [Documentation Structure](#documentation-structure)
    * [Practical Guidance for AI Assistants](#practical-guidance-for-ai-assistants)
      * [Documentation drift](#documentation-drift)

## AI documentation map [ᐞ](#table-of-contents)

<a id="ai-documentation-map"></a>

Use the repository AI guidance in this order:

1. `.github/copilot-instructions.md` - short repository-level operational rules
2. `CLAUDE.md` - long-form project context, architecture, and workflow notes
3. `doc/AI_RULES.md` - AI policy maintenance and CI strategy guidance
4. `.github/pull_request_template.md` - human review checklist for pull requests

If one of these documents drifts from the implementation, prefer the actual
repository code, scripts, and CI configuration as the source of truth.

## Version sources of truth [ᐞ](#table-of-contents)

<a id="version-sources-of-truth"></a>

To avoid documentation drift, this file intentionally avoids mirroring most
exact dependency and tooling versions.

For current versions, use these files as the source of truth:

* `composer.json` for PHP, Symfony, Doctrine, and all package versions
* `Dockerfile` and `Dockerfile_dev` for container PHP and base image versions
* `phpstan.neon.dist` for PHPStan level and configuration
* `psalm.xml` for Psalm configuration
* `ecs.php` for ECS (Easy Coding Standard) rules
* `phpunit.xml.dist` for PHPUnit configuration

If a version matters for implementation, read it from those files instead of
copying it into long-form documentation.

## Project Architecture [ᐞ](#table-of-contents)

<a id="project-architecture"></a>

* **Type:** JSON REST API Backend
* **Pattern:** Resource-based REST architecture with service layer
* **Authentication:**
  * JWT (Lexik JWT Bundle)
  * API key authentication
* **ORM:** Doctrine ORM with migrations
  * Migrations located in `migrations/`
  * Entities in `src/Entity/`
  * Repositories in `src/Repository/`
  * MariaDB database
* **Key Layers:**
  * Controller Layer: `src/Controller/` and `src/Rest/`
  * Service Layer: `src/Service/` and `src/Resource/`
  * Repository Layer: `src/Repository/`
  * Entity Layer: `src/Entity/`
  * DTO Layer: `src/DTO/`
  * Security Layer: `src/Security/`
  * AutoMapper: `src/AutoMapper/`
  * Value Resolvers: `src/ValueResolver/`
  * Decorators: `src/Decorator/`

## Development Tools [ᐞ](#table-of-contents)

<a id="development-tools"></a>

### Static Analysis [ᐞ](#table-of-contents)

<a id="static-analysis"></a>

* **PHPStan** (Level: max) - Static analysis
* **Psalm** - Static analysis with type checking
* **PHP_CodeSniffer** - Code style checking
* **ECS (Easy Coding Standard)** - Code style fixing (primary)
* **PHPInsights** - Code quality and architecture analysis
* **Rector** - Automated code refactoring and upgrades

### Test runners [ᐞ](#table-of-contents)

<a id="test-runners"></a>

* **PHPUnit** - Unit and integration testing
* **Fastest** - Parallel test execution (this will be removed in future)
* **Infection** - Mutation testing (not used heavily, optional)

### Code Quality & Analysis [ᐞ](#table-of-contents)

<a id="code-quality-and-analysis"></a>

* **PHPMetrics** - Code metrics and quality reports
* **PHPLint** / **PHP-Parallel-Lint** - Syntax checking
* **PHPLOC** - Project size and statistics
* **Composer Tools** - Dependency analysis

## Common Development Commands [ᐞ](#table-of-contents)

<a id="common-development-commands"></a>

### Container Management [ᐞ](#table-of-contents)

<a id="container-management"></a>

* `make start` - Start all containers (foreground, preferred way)
* `make daemon` - Start all containers (background)
* `make stop` - Stop all containers
* `make logs` - View container logs
* `make bash` or `make fish` - Get shell inside PHP container
* The primary container for project commands is the `php` service container
  (`symfony-backend-php-fpm`).
* If containers are not running, start them from project root with `make start`
  or `make daemon`.
* Use the running `php` container or IDE Dev Container as the default execution
  environment for project commands.
* Node.js tooling is available in the containerized environment via `nvm`; use
  `make lint-markdown` for markdown/documentation checks.

### Code Quality [ᐞ](#table-of-contents)

<a id="code-quality"></a>

* `make phpcs` - Run PHP CodeSniffer
* `make phpstan` - Run PHPStan static analysis
* `make psalm` - Run Psalm static analysis
* `make ecs` - Check code style
* `make ecs-fix` - Fix code style issues automatically
* `make phplint` - Run PHPLint
* `make php-parallel-lint` - Run php-parallel-lint
* `make phploc` - Run PHPLoc metrics summary
* `make lint-markdown` - Lint Markdown documentation files
* `make phpinsights` - Run comprehensive code quality checks
* `make check-security` - Check installed dependencies for known vulnerabilities

### Testing commands [ᐞ](#table-of-contents)

<a id="testing-commands"></a>

* `make run-tests` - Run all tests (single thread)
* `make run-tests-fastest` - Run tests in parallel (this will be removed in future)
* `make infection` - Run mutation testing (not heavily used)

### Database [ᐞ](#table-of-contents)

<a id="database"></a>

* `bin/console doctrine:migrations:migrate` - Run migrations
* `bin/console doctrine:migrations:diff` - Generate migration from entity changes
* `bin/console doctrine:schema:validate` - Validate database schema

### Dependencies [ᐞ](#table-of-contents)

<a id="dependencies"></a>

* `make update` - Update composer dependencies
* `make check-dependencies-patch` - Check for patch updates
* `make check-dependencies-minor` - Check for minor updates
* `make check-dependencies-latest` - Check for latest versions
* `make check-security` - Check for security vulnerabilities

## Development Workflow [ᐞ](#table-of-contents)

<a id="development-workflow"></a>

### Adding New REST Endpoints [ᐞ](#table-of-contents)

<a id="adding-new-rest-endpoints"></a>

This project uses a resource-based approach:

1. Create/modify entity in `src/Entity/`
2. Generate migration: `bin/console doctrine:migrations:diff`
3. Review and edit migration file if needed
4. Run migration: `bin/console doctrine:migrations:migrate`
5. Create/update DTO(s) in `src/DTO/`
6. Create/update repository in `src/Repository/`
7. Create/update resource class in `src/Resource/`
8. Create/update REST controller in `src/Rest/`
9. Write tests in appropriate `tests/` subdirectory
10. Run tests: `make run-tests-fastest`
11. Check static analyzers: `make phpcs`, `make ecs`, `make phplint`,
    `make php-parallel-lint`, `make psalm`, `make phpstan`, `make phploc`,
    `make phpinsights`, `make lint-markdown`
12. Fix issues: `make ecs-fix`

### Before Committing [⇩](#table-of-contents)

<a id="before-committing"></a>

Always run these commands before committing:

```bash
make ecs-fix           # Auto-fix code style
make phpcs             # PHP CodeSniffer
make ecs               # EasyCodingStandard check
make phplint           # PHPLint
make php-parallel-lint # Parallel lint
make phpstan           # Static analysis
make psalm             # Type checking
make phploc            # Project metrics summary used by CI
make phpinsights       # Quality thresholds check
make check-security    # Dependency vulnerability check
make lint-markdown     # Markdown/documentation linting
make run-tests-fastest # Run all tests in parallel
```

## Testing [⇩](#table-of-contents)

<a id="testing"></a>

### Test Structure [⇩](#table-of-contents)

<a id="test-structure"></a>

* `tests/E2E/` - End-to-end API tests
* `tests/Functional/` - Functional tests with database
* `tests/Integration/` - Integration tests for components
* `tests/Unit/` - Unit tests for isolated components
* `tests/Utils/` - Testing utilities and helpers
* `tests/DataFixtures/` - Test data fixtures

### Running Tests [⇩](#table-of-contents)

<a id="running-tests"></a>

```bash
# All tests (single thread)
make run-tests

# All tests (parallel - FASTER, recommended)
make run-tests-fastest

# Mutation testing
make infection
```

### Test Environment [⇩](#table-of-contents)

<a id="test-environment"></a>

* Uses separate test database
* Environment: `APP_ENV=test`
* Configuration: `phpunit.xml.dist` and `phpunit.fastest.xml`
* Fixtures loaded via `tests/DataFixtures/`

## Security [⇩](#table-of-contents)

<a id="security"></a>

### Authentication [⇩](#table-of-contents)

<a id="authentication"></a>

* **JWT Tokens:** Using Lexik JWT Authentication Bundle
* **API Keys:** Managed via `api-key:management` console command
* **User Management:** Available via `user:management` console command

### Key Security Files [⇩](#table-of-contents)

<a id="key-security-files"></a>

* `config/packages/security.yaml` - Security configuration
* `config/jwt/` - JWT key storage (generated via `make generate-jwt-keys`)
* `secrets/` - Application secrets storage

### Security Best Practices [⇩](#table-of-contents)

<a id="security-best-practices"></a>

* Never commit `.env.local` or JWT keys to version control
* Use proper user roles and permissions
* Validate all input data with Symfony validation
* Use DTOs to control data exposure
* Run security checks: `make check-security`

## Configuration [⇩](#table-of-contents)

<a id="configuration"></a>

### Environment Files [⇩](#table-of-contents)

<a id="environment-files"></a>

* `.env` - Default configuration (committed)
* `.env.local` - Local overrides (ignored by git)
* `APPLICATION_CONFIG` - Path to JSON config file (default:
  `secrets/application.json`)

### Key Configuration Files [⇩](#table-of-contents)

<a id="key-configuration-files"></a>

* `config/services.yaml` - Service configuration
* `config/packages/` - Bundle configurations
* `config/routes/` - Route definitions
* `secrets/application.json` - Application-specific configuration

### View Current Configuration [⇩](#table-of-contents)

<a id="view-current-configuration"></a>

Use `make configuration` to view current application configuration.

## Documentation Structure [⇩](#table-of-contents)

<a id="documentation-structure"></a>

* `README.md` - Project overview and installation
* `CLAUDE.md` - This file - long-form AI assistant context
* `.github/copilot-instructions.md` - Short operational rules for AI assistants
* `doc/AI_RULES.md` - AI policy maintenance and CI strategy guidance
* `doc/README.md` - Documentation index
* `doc/COMMANDS.md` - Complete command reference (Makefile + Console)
* `doc/DEVELOPMENT.md` - Development best practices and workflow
* `doc/TESTING.md` - Testing strategies and guidelines
* `doc/CONCEPTS_AND_FEATURES.md` - Architecture concepts and features
* `doc/CUSTOM_CONFIGURATION.md` - Configuration management
* `doc/PHPSTORM.md` - PhpStorm IDE setup
* `doc/XDEBUG.md` - Debugging setup and usage
* `doc/INSTALLATION_WITHOUT_DOCKER.md` - Non-Docker installation
* `doc/SPEED_UP_DOCKER_COMPOSE.md` - Performance optimization
* `doc/USAGE_CHECKLIST.md` - Pre-deployment checklist

## Practical Guidance for AI Assistants [⇩](#table-of-contents)

<a id="practical-guidance-for-ai-assistants"></a>

When making changes in this repository:

1. Use the resource-based REST architecture; keep controllers thin.
2. Follow existing Doctrine entity, repository, and migration patterns.
3. Use DTOs for API input and output; do not expose entities directly.
4. Use the AutoMapper for entity-to-DTO and DTO-to-entity mapping.
5. Keep classes in `src/Controller/` as thin as possible and delegate business
   logic to resources/services.
6. For custom controllers in `src/Controller/`, prefer the `__invoke` pattern
   (one controller class per endpoint).
7. Treat trait-based controllers in `src/Rest/` as an exception to the
   `__invoke` rule.
8. Declare `declare(strict_types=1);` in every PHP file.
9. Keep changes compatible with PHPStan (max level) and Psalm.
10. Write tests for new functionality and run the test suite before committing.
11. Prefer the smallest change that fully solves the task.
12. Avoid unrelated refactors unless explicitly required.
13. Before commit, run the full static-analyzer suite used by CI
    (`make phpcs`, `make ecs`, `make phplint`, `make php-parallel-lint`,
    `make psalm`, `make phpstan`, `make phploc`, `make phpinsights`,
    `make lint-markdown`) inside the running `php` container or IDE Dev
    Container, then run tests.
14. Use the `php` container (`symfony-backend-php-fpm`) as the default command
    target and start it with `make start`/`make daemon` when needed.
15. For markdown/documentation checks that require Node.js tooling, use
    containerized `nvm` + `npx` instead of host-level installs.
16. Do not create commits unless the developer explicitly asks for a commit.
17. After completing a task, provide a short change summary with touched files
    and validation steps that were run (or intentionally skipped).
18. If requirements are ambiguous or incomplete, ask the developer for
    clarification before implementation.
19. Do not assume hidden requirements or acceptance criteria; confirm uncertain
    behavior explicitly.
20. Ask for explicit approval before making non-trivial API, database, security,
    or architecture decisions.
21. Update relevant documentation when code changes affect behavior,
    architecture, workflow steps, commands, or review expectations.
22. In the handoff summary, propose commit message(s) per logical change using
    the repository pattern `Type(scope): short description` (for example
    `Chore(ops): update AI documentation rules`).
23. For Markdown documentation changes, follow `README.md` as formatting
    baseline (main title, anchors, table of contents, back-to-top links,
    back-to-previous footer links, and `*` list markers).

### Documentation drift [⇩](#table-of-contents)

<a id="documentation-drift"></a>

This file is long-form context, not the only rules source. Keep it aligned with:

* `.github/copilot-instructions.md`
* `doc/AI_RULES.md`
* `.github/pull_request_template.md`
* actual repository scripts and CI workflows

---

*This document is maintained for AI assistants and contributors who need a
high-level map of the project's architecture, workflow, and repository
conventions.*

---

[Back to previous](README.md)
