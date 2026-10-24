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

Use the [README AI guidance inventory](README.md#ai-assisted-development) for
the canonical document list and reading order.

This order is for navigation, not precedence. Platform instructions apply
first, and the actual code, scripts, tests, configuration, and CI are the
source of truth for implementation details.

## Version sources of truth [ᐞ](#table-of-contents)

<a id="version-sources-of-truth"></a>

To avoid documentation drift, this file intentionally avoids mirroring most
exact dependency and tooling versions.

For current versions, use these files as the source of truth:

* `composer.json` for PHP, Symfony, Doctrine, and all package versions
* `Dockerfile` for shared PHP base image versions and development/production targets
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
* **Infection** - Optional mutation testing; run `make infection`

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
* Do not execute project PHP tooling directly on the host by default; use
  container-aware `make` targets or run inside the `php` container.
* Node.js tooling is available in the containerized environment via `nvm`; use
  `make lint-markdown` for markdown/documentation checks.
* Host fallbacks for project tooling are allowed only when container execution is
  unavailable, and that blocker must be stated explicitly in the handoff.

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

* `make run-tests` - Run all tests; selects phpdbg when available, otherwise
  pure PHP
* `make run-tests-php` - Run all tests via pure PHP (the CI application-test
  command)
* `make run-tests-phpdbg` - Run all tests via phpdbg
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

For CRUD resources, follow the
[add-rest-resource skill](.github/skills/add-rest-resource/SKILL.md), which
covers this repository's entity, migration, DTO, resource, controller, and
test conventions. For non-CRUD endpoints, use the
[custom API endpoint skill](.github/skills/add-custom-api-endpoint/SKILL.md).
The [skills index](.github/skills/README.md) routes other task types to their
specific workflows.

### Before Committing [⇩](#table-of-contents)

<a id="before-committing"></a>

Before committing, follow the validation checklist in
[`AGENTS.md`](AGENTS.md#testing-and-validation). This file's command sections
are a reference; the agent instructions own the required validation workflow.

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

For targeted tests, use just `phpunit` from the repository root inside the PHP
development container; no explicit runner path is needed. Open a container
shell with `make bash` or `make fish` from the host, or use the IDE Dev Container
terminal. See [the testing guide](doc/TESTING.md) for details and
[the test-writing skill](.github/skills/write-tests/SKILL.md) for test selection
and conventions.

```bash
# A targeted test class (inside the PHP development container)
phpunit tests/Unit/Rest/SearchTermTest.php

# All tests (single thread)
make run-tests

# Use the same pure-PHP runner as the CI application-test job
make run-tests-php

# Mutation testing
make infection
```

### Test Environment [⇩](#table-of-contents)

<a id="test-environment"></a>

* Uses separate test database
* Environment: `APP_ENV=test`
* Configuration: `phpunit.xml.dist`
* Fixtures loaded via `tests/DataFixtures/`

## Security [⇩](#table-of-contents)

<a id="security"></a>

For the complete security policy, vulnerability reporting procedures, and
authentication details, see [`doc/SECURITY.md`](doc/SECURITY.md).

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

Follow [`doc/SECURITY.md`](doc/SECURITY.md) for security policy and
[`AGENTS.md`](AGENTS.md) for the actionable repository-wide security and
validation requirements.

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
* `AGENTS.md` - Repository instructions for AI coding agents
* `CLAUDE.md` - This file - long-form AI assistant context
* `.github/copilot-instructions.md` - Copilot entry point for repository guidance
* `doc/AI_RULES.md` - AI policy maintenance and CI strategy guidance
* `.github/skills/README.md` - Task-specific AI skills index
* `doc/README.md` - Documentation index
* `doc/COMMANDS.md` - Complete command reference (Makefile + Console)
* `doc/DEVELOPMENT.md` - Development best practices and workflow
* `doc/TESTING.md` - Testing strategies and guidelines
* `doc/CONCEPTS_AND_FEATURES.md` - Architecture concepts and features
* `doc/CUSTOM_CONFIGURATION.md` - Configuration management
* `doc/DOCKER_MULTI_PLATFORM.md` - Multi-platform Docker base image pinning
* `doc/PHPSTORM.md` - PhpStorm IDE setup
* `doc/XDEBUG.md` - Debugging setup and usage
* `doc/INSTALLATION_WITHOUT_DOCKER.md` - Non-Docker installation
* `doc/SPEED_UP_DOCKER_COMPOSE.md` - Performance optimization
* `doc/USAGE_CHECKLIST.md` - Pre-deployment checklist

## Practical Guidance for AI Assistants [⇩](#table-of-contents)

<a id="practical-guidance-for-ai-assistants"></a>

For actionable repository-wide instructions, including architecture, change
scope, validation, and handoff expectations, follow
[`AGENTS.md`](AGENTS.md). This guide focuses on project context and command
references; task-specific workflows live in the
[skills index](.github/skills/README.md).

### Documentation drift [⇩](#table-of-contents)

<a id="documentation-drift"></a>

Keep project architecture, command, and setup descriptions aligned with the
implementation and workflows. The canonical AI-document inventory is in
[`README.md`](README.md#ai-assisted-development); AI-specific policy and CI
maintenance guidance belongs in [`doc/AI_RULES.md`](doc/AI_RULES.md).

---

*This document is maintained for AI assistants and contributors who need a
high-level map of the project's architecture, workflow, and repository
conventions.*

---

[Back to previous](README.md)
