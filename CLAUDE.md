# What is this?

<a id="what-is-this"></a>

`CLAUDE.md` is the long-form AI assistant guide for this repository. It provides
project context, architecture expectations, workflow conventions, and practical
rules for making Symfony backend changes that stay aligned with repository
standards.

This repository is a production-ready Symfony JSON REST API backend template
with Docker setup for local development. It is designed to be consumed by
frontend applications or other backend services.

## Table of Contents

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [AI documentation map](#ai-documentation-map)
  * [Version sources of truth](#version-sources-of-truth)
  * [Project Architecture](#project-architecture)
  * [Development Tools](#development-tools)
  * [Common Development Commands](#common-development-commands)
  * [Development Workflow](#development-workflow)
  * [Testing](#testing)
  * [Security](#security)
  * [Configuration](#configuration)
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

* **PHPStan** (Level: max) - Static analysis
* **Psalm** - Static analysis with type checking
* **PHP_CodeSniffer** - Code style checking
* **ECS (Easy Coding Standard)** - Code style fixing (primary)
* **PHPInsights** - Code quality and architecture analysis
* **Rector** - Automated code refactoring and upgrades

### Test runners [ᐞ](#table-of-contents)

* **PHPUnit** - Unit and integration testing
* **Fastest** - Parallel test execution (this will be removed in future)
* **Infection** - Mutation testing (not used heavily, optional)

### Code Quality & Analysis [ᐞ](#table-of-contents)

* **PHPMetrics** - Code metrics and quality reports
* **PHPLint** / **PHP-Parallel-Lint** - Syntax checking
* **PHPLOC** - Project size and statistics
* **Composer Tools** - Dependency analysis

## Common Development Commands [ᐞ](#table-of-contents)

<a id="common-development-commands"></a>

### Container Management [ᐞ](#table-of-contents)

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
* Node.js tooling is available in the containerized environment via `nvm`, so
  `npx`-based markdown/documentation checks can run there too.

### Code Quality [ᐞ](#table-of-contents)

* `make phpstan` - Run PHPStan static analysis
* `make psalm` - Run Psalm static analysis
* `make ecs` - Check code style
* `make ecs-fix` - Fix code style issues automatically
* `make lint-markdown` - Lint Markdown documentation files
* `make phpinsights` - Run comprehensive code quality checks

### Testing commands [ᐞ](#table-of-contents)

* `make run-tests` - Run all tests (single thread)
* `make run-tests-fastest` - Run tests in parallel (this will be removed in future)
* `make infection` - Run mutation testing (not heavily used)

### Database [ᐞ](#table-of-contents)

* `bin/console doctrine:migrations:migrate` - Run migrations
* `bin/console doctrine:migrations:diff` - Generate migration from entity changes
* `bin/console doctrine:schema:validate` - Validate database schema

### Dependencies [ᐞ](#table-of-contents)

* `make update` - Update composer dependencies
* `make check-dependencies-patch` - Check for patch updates
* `make check-dependencies-minor` - Check for minor updates
* `make check-dependencies-latest` - Check for latest versions
* `make check-security` - Check for security vulnerabilities

## Development Workflow [ᐞ](#table-of-contents)

<a id="development-workflow"></a>

### Adding New REST Endpoints [ᐞ](#table-of-contents)

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
11. Check code quality: `make ecs && make phpstan && make psalm`
12. Fix issues: `make ecs-fix`

### Before Committing [ᐞ](#table-of-contents)

Always run these commands:

```bash
make ecs-fix           # Auto-fix code style
make phpstan           # Static analysis
make psalm             # Type checking
make run-tests-fastest # Run all tests in parallel
```

For Markdown/documentation changes, also run:

```bash
make lint-markdown
```

## Testing [ᐞ](#table-of-contents)

<a id="testing"></a>

### Test Structure [ᐞ](#table-of-contents)

* `tests/E2E/` - End-to-end API tests
* `tests/Functional/` - Functional tests with database
* `tests/Integration/` - Integration tests for components
* `tests/Unit/` - Unit tests for isolated components
* `tests/Utils/` - Testing utilities and helpers
* `tests/DataFixtures/` - Test data fixtures

### Running Tests [ᐞ](#table-of-contents)

```bash
# All tests (single thread)
make run-tests

# All tests (parallel - FASTER, recommended)
make run-tests-fastest

# Mutation testing
make infection
```

### Test Environment [ᐞ](#table-of-contents)

* Uses separate test database
* Environment: `APP_ENV=test`
* Configuration: `phpunit.xml.dist` and `phpunit.fastest.xml`
* Fixtures loaded via `tests/DataFixtures/`

## Security [ᐞ](#table-of-contents)

<a id="security"></a>

### Authentication [ᐞ](#table-of-contents)

* **JWT Tokens:** Using Lexik JWT Authentication Bundle
* **API Keys:** Managed via `api-key:management` console command
* **User Management:** Available via `user:management` console command

### Key Security Files [ᐞ](#table-of-contents)

* `config/packages/security.yaml` - Security configuration
* `config/jwt/` - JWT key storage (generated via `make generate-jwt-keys`)
* `secrets/` - Application secrets storage

### Security Best Practices [ᐞ](#table-of-contents)

* Never commit `.env.local` or JWT keys to version control
* Use proper user roles and permissions
* Validate all input data with Symfony validation
* Use DTOs to control data exposure
* Run security checks: `make check-security`

## Configuration [ᐞ](#table-of-contents)

<a id="configuration"></a>

### Environment Files [ᐞ](#table-of-contents)

* `.env` - Default configuration (committed)
* `.env.local` - Local overrides (ignored by git)
* `APPLICATION_CONFIG` - Path to JSON config file (default:
  `secrets/application.json`)

### Key Configuration Files [ᐞ](#table-of-contents)

* `config/services.yaml` - Service configuration
* `config/packages/` - Bundle configurations
* `config/routes/` - Route definitions
* `secrets/application.json` - Application-specific configuration

### View Current Configuration [ᐞ](#table-of-contents)

Use `make configuration` to view current application configuration.

## Documentation Structure [ᐞ](#table-of-contents)

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

## Practical Guidance for AI Assistants [ᐞ](#table-of-contents)

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
13. Run the smallest relevant validation commands inside the running `php`
    container or IDE Dev Container.
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

### Documentation drift [ᐞ](#table-of-contents)

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
