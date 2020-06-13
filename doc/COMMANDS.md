# What is this?

This document contains all custom commands that you can use within this
application during development stage.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Commands](#commands)
    * [Makefile](#makefile)
    * [Symfony console](#symfony-console)
    * [Custom commands](#custom-commands)
      * [user:management](#usermanagement)
      * [api-key:management](#api-keymanagement)
      * [utils:create-date-dimension-entities](#utilscreate-date-dimension-entities)

## Commands

Note that all of these commands are intended to be executed either inside
docker container or your local/dedicated server.

There is also exception for this;

```bash
make bash
```

That command is shortcut for `docker-compose exec php bash` command and you
can use that command within your host machine.

### Makefile

Symfony Flex comes with `Makefile` configuration so that you can easily run
some generic commands via `make` command. Below is a list of currently
supported (main commands) make commands, note that you can get this same list
with just running `make` command:

```bash
bash                        # Get bash inside PHP container
cache-clear                 # Clears the cache
cache-warmup                # Warms up an empty cache
check-dependencies          # Checks if any vendor dependency can be updated
clear-tools                 # Clears all tools dependencies
ecs-fix                     # Runs The Easiest Way to Use Any Coding Standard
                            # to fix issues
ecs                         # Runs The Easiest Way to Use Any Coding Standard
generate-jwt-keys           # Generates JWT auth keys
infection                   # Runs Infection to codebase
lint-configuration          # Lint current defined `application.json` that it
                            # contains valid JSON
lint-yaml                   # Lint config YAML files
local-configuration         # Create local configuration files
normalize-composer          # Normalizes `composer.json` content
phpcs                       # Runs PHP CodeSniffer
phpinsights                 # Runs PHP Insights
phploc                      # Runs `phploc` and create json output
phpmetrics                  # Generates PhpMetrics static analysis
phpstan                     # Runs PHPStan static analysis tool
psalm-shepherd              # Runs Psalm static analysis tool + report results
                            # to shepherd
psalm                       # Runs Psalm static analysis tool
report-fastest              # Creates clover and JUnit xml from fastest run
run-tests-fastest-php       # Runs all test via fastest (pure PHP)
run-tests-fastest-phpdbg    # Runs all test via fastest (phpdbg)
run-tests-fastest           # Runs all test via fastest (Uses phpdbg if that is
                            # installed)
run-tests-php               # Runs all tests via phpunit (pure PHP)
run-tests-phpdbg            # Runs all tests via phpunit (phpdbg)
run-tests                   # Runs all tests via phpunit (Uses phpdbg if that
                            # is installed)
serve                       # Runs a local web server
start-build                 # Start application in development mode and build
                            # containers
start                       # Start application in development mode
stop                        # Stop application containers
update-bin                  # Update composer bin dependencies
update                      # Update composer dependencies
```

### Symfony console

You can list all Symfony console commands via following command:

```bash
./bin/console
```

or

```bash
console
```

### Custom commands

Project contains following custom console commands to help eg. user management:

```bash
./bin/console user:management                       # To manage your users and
                                                    # user groups
./bin/console api-key:management                    # To manage your API keys
./bin/console make:rest-api                         # To create skeleton
                                                    # classes for new REST
                                                    # resource
./bin/console utils:create-date-dimension-entities  # Console command to create
                                                    # 'DateDimension' entities.
```

#### user:management

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

#### api-key:management

This command is just a wrapper for following commands:

```bash
./bin/console api-key:create          # To create API key
./bin/console api-key:edit            # To edit API key
./bin/console api-key:change-token    # To change API key token
./bin/console api-key:remove          # To remove API key
./bin/console api-key:list            # To list API keys
```

#### utils:create-date-dimension-entities

Command to create `DateDimension` entities that can be used with date/time
related report queries.

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
