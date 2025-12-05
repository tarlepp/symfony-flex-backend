# General Guidelines for Interacting with Claude

- This is a Symfony JSON REST API backend template with Docker setup for local
  development.
- Check the README.md for detailed installation and usage instructions.
- Use `make` commands to manage Docker containers and application tasks.
- For frontend integration, refer to the "Frontend?" section in the README.md.
- For additional resources and links, see the "Resources" and "External links
  / resources" sections.

# Project Architecture

- **Type:** JSON REST API Backend
- **Pattern:** Resource-based REST architecture with service layer
- **Authentication:**
  - JWT (Lexik JWT Bundle)
  - API key authentication
- **ORM:** Doctrine ORM with migrations
  - Migrations located in `migrations/`
  - Entities in `src/Entity/`
  - Repositories in `src/Repository/`
  - MariaDB database
- **Key Layers:**
  - Controller Layer: `src/Controller/` and `src/Rest/`
  - Service Layer: `src/Service/` and `src/Resource/`
  - Repository Layer: `src/Repository/`
  - Entity Layer: `src/Entity/`
  - DTO Layer: `src/DTO/`
  - Security Layer: `src/Security/`
  - AutoMapper: `src/AutoMapper/`
  - Value Resolvers: `src/ValueResolver/`
  - Decorators: `src/Decorator/`

# Version Requirements

- **PHP:** 8.4+ (8.4.11 platform requirement)
- **Symfony:** 7.4.*
- **Database:** MariaDB 10.7+
- **Docker Engine:** Required for local development
- **Composer:** 2.x

# Development Tools

## Static Analysis

- **PHPStan** (Level: max) - Static analysis
- **Psalm** - Static analysis with type checking
- **PHP_CodeSniffer** - Code style checking
- **ECS (Easy Coding Standard)** - Code style fixing (primary)
- **PHPInsights** - Code quality and architecture analysis
- **Rector** - Automated code refactoring and upgrades

## Testing

- **PHPUnit** - Unit and integration testing
- **Fastest** - Parallel test execution (this will be removed in future)
- **Infection** - Mutation testing (not used heavily, optional)

## Code Quality & Analysis

- **PHPMetrics** - Code metrics and quality reports
- **PHPLint** / **PHP-Parallel-Lint** - Syntax checking
- **PHPLOC** - Project size and statistics
- **Composer Tools** - Dependency analysis

# Common Development Commands

## Container Management

- `make start` - Start all containers (foreground, preferred way)
- `make daemon` - Start all containers (background)
- `make stop` - Stop all containers
- `make logs` - View container logs
- `make bash` or `make fish` - Get shell inside PHP container

## Code Quality

- `make phpstan` - Run PHPStan static analysis
- `make psalm` - Run Psalm static analysis
- `make ecs` - Check code style
- `make ecs-fix` - Fix code style issues automatically
- `make phpinsights` - Run comprehensive code quality checks

## Testing

- `make run-tests` - Run all tests (single thread)
- `make run-tests-fastest` - Run tests in parallel (removed in future)
- `make infection` - Run mutation testing (not heavily used)

## Database

- `bin/console doctrine:migrations:migrate` - Run migrations
- `bin/console doctrine:migrations:diff` - Generate migration from entity changes
- `bin/console doctrine:schema:validate` - Validate database schema

## Dependencies

- `make update` - Update composer dependencies
- `make check-dependencies-patch` - Check for patch updates
- `make check-dependencies-minor` - Check for minor updates
- `make check-dependencies-latest` - Check for latest versions
- `make check-security` - Check for security vulnerabilities

# Development Workflow

## Adding New REST Endpoints

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

## Before Committing

Always run these commands:

```bash
make ecs-fix           # Auto-fix code style
make phpstan           # Static analysis
make psalm             # Type checking
make run-tests-fastest # Run all tests in parallel
```

# Testing

## Test Structure

- `tests/E2E/` - End-to-end API tests
- `tests/Functional/` - Functional tests with database
- `tests/Integration/` - Integration tests for components
- `tests/Unit/` - Unit tests for isolated components
- `tests/Utils/` - Testing utilities and helpers
- `tests/DataFixtures/` - Test data fixtures

## Running Tests

```bash
# All tests (single thread)
make run-tests

# All tests (parallel - FASTER, recommended)
make run-tests-fastest

# Mutation testing
make infection
```

## Test Environment

- Uses separate test database
- Environment: `APP_ENV=test`
- Configuration: `phpunit.xml.dist` and `phpunit.fastest.xml`
- Fixtures loaded via `tests/DataFixtures/`

# Security

## Authentication

- **JWT Tokens:** Using Lexik JWT Authentication Bundle
- **API Keys:** Managed via `api-key:management` console command
- **User Management:** Available via `user:management` console command

## Key Security Files

- `config/packages/security.yaml` - Security configuration
- `config/jwt/` - JWT key storage (generated via `make generate-jwt-keys`)
- `secrets/` - Application secrets storage

## Security Best Practices

- Never commit `.env.local` or JWT keys to version control
- Use proper user roles and permissions
- Validate all input data with Symfony validation
- Use DTOs to control data exposure
- Run security checks: `make check-security`

# Configuration

## Environment Files

- `.env` - Default configuration (committed)
- `.env.local` - Local overrides (ignored by git)
- `APPLICATION_CONFIG` - Path to JSON config file (default:
  `secrets/application.json`)

## Key Configuration Files

- `config/services.yaml` - Service configuration
- `config/packages/` - Bundle configurations
- `config/routes/` - Route definitions
- `secrets/application.json` - Application-specific configuration

## View Current Configuration

Use `make configuration` to view current application configuration.

# Documentation Structure

## Quick Reference

- `README.md` - Project overview and installation
- `CLAUDE.md` - This file - AI assistant guidelines
- `doc/README.md` - Documentation index

## Detailed Documentation

- `doc/COMMANDS.md` - Complete command reference (Makefile + Console)
- `doc/DEVELOPMENT.md` - Development best practices and workflow
- `doc/TESTING.md` - Testing strategies and guidelines
- `doc/CONCEPTS_AND_FEATURES.md` - Architecture concepts and features
- `doc/CUSTOM_CONFIGURATION.md` - Configuration management
- `doc/PHPSTORM.md` - PhpStorm IDE setup
- `doc/XDEBUG.md` - Debugging setup and usage
- `doc/INSTALLATION_WITHOUT_DOCKER.md` - Non-Docker installation
- `doc/SPEED_UP_DOCKER_COMPOSE.md` - Performance optimization
- `doc/USAGE_CHECKLIST.md` - Pre-deployment checklist

# Guidelines for AI Code Assistants

## When Modifying Code

1. **Always check existing patterns** in similar files before creating new code
2. **Follow the resource-based architecture** for new endpoints
3. **Use strict types** - All PHP files should declare `declare(strict_types=1);`
4. **Write tests** for any new functionality
5. **Run code quality tools** after changes

## Common Patterns to Follow

- **Entities:** Use Doctrine attributes, implement proper getters/setters
- **DTOs:** Immutable where possible, use validation constraints
- **Repositories:** Extend `BaseRepository`, use QueryBuilder
- **Resources:** Handle business logic, coordinate between repositories
- **Controllers:** Thin controllers, delegate to resources/services
- **Tests:** Use fixtures, test happy path and edge cases
- **Decorators:** Used for cross-cutting concerns (e.g., `StopwatchDecorator` for performance monitoring)

## Code Generation Rules

- **Never remove security checks** or authentication
- **Always validate input** using Symfony validation
- **Use type hints** for all parameters and return types
- **Document complex logic** with PHPDoc blocks
- **Follow PSR-12** coding standard
- **Respect existing code organization** and patterns
- **Use readonly properties** where appropriate (PHP 8.1+ feature)

## Before Suggesting Code Changes

1. Check if similar functionality exists
2. Review related tests
3. Consider security implications
4. Ensure backward compatibility
5. Verify against static analysis rules (PHPStan, Psalm, ECS, PHPInsights)

## When Unsure

- Reference `doc/CONCEPTS_AND_FEATURES.md` for architecture
- Check `doc/DEVELOPMENT.md` for best practices
- Look at existing similar implementations
- Ask for clarification rather than making assumptions
