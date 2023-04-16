# What is this?

This document contains all custom commands that you can use within this
application during development stage.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Commands](#commands-table-of-contents)
    * [Makefile](#makefile-table-of-contents)
    * [Symfony console](#symfony-console-table-of-contents)
    * [Custom commands](#custom-commands-table-of-contents)
      * [user:management](#usermanagement-table-of-contents)
      * [api-key:management](#api-keymanagement-table-of-contents)
      * [utils:create-date-dimension-entities](#utilscreate-date-dimension-entities-table-of-contents)

## Commands [ᐞ](#table-of-contents)

Note that all of these commands are intended to be executed either inside
docker container or your local/dedicated server.

There is also exception for this;

```bash
make bash
OR
make fish
```

That command is shortcut for `docker-compose exec php bash/fish` command and
you can use that command within your host machine. Note that default user
inside that `php` container is `dev` and it has been bind to your host machine
user and group (uid = 1000, gid = 1000) - so that you don't have issues within
file permissions.

Note that if you host machine user _isn't_ bind to those mentioned values:

```bash
uid = 1000 
gid = 1000
```

You need to create `docker-compose.override.yml` file and specify used user
information there.

### Makefile [ᐞ](#table-of-contents)

Symfony Flex comes with `Makefile` configuration so that you can easily run
some generic commands via `make` command. Below is a list of currently
supported (main commands) make commands, note that you can get this same list
with just running `make` command:

```bash
bash                        # Get bash inside PHP container
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
clear                       # Clear _all_ vendor and tool dependencies
clear-tools                 # Clears all tools dependencies
composer-normalize          # Normalizes `composer.json` file content
composer-require-checker    # Check the defined dependencies against your code
composer-unused             # Show unused packages by scanning and comparing
                            # package namespaces against your source
composer-validate           # Validate `composer.json` file content
configuration               # Prints out application current configuration
daemon                      # Start application in development mode in
                            # background
daemon-build                # Build containers and start application in
                            # development mode in background
docker-kill-containers      # Kill all running docker containers
docker-prune                # Kill all running containers and prune all docker
                            # stuff
docker-remove-containers    # Remove all docker containers
docker-remove-images        # Remove all docker images
ecs-fix                     # Runs The Easiest Way to Use Any Coding Standard
                            # to fix issues
ecs                         # Runs The Easiest Way to Use Any Coding Standard
fish                        # Get fish inside PHP container
generate-jwt-keys           # Generates JWT auth keys
generate-ssl-cert           # Generate self signed SSL certificates
infection                   # Runs Infection to codebase
install-bin                 # Install composer bin dependencies
lint-configuration          # Lint current defined `application.json` that it
                            # contains valid JSON
lint-yaml                   # Lint config YAML files
local-configuration         # Create local configuration files
logs                        # Show logs from all containers
phpcs                       # Runs PHP CodeSniffer
phpinsights                 # Runs PHP Insights
phplint                     # Runs PHPLint
phploc                      # Runs `phploc` and create json output
phpmetrics                  # Generates PhpMetrics static analysis
phpstan-github              # Runs PHPStan static analysis tool (GitHub)
phpstan                     # Runs PHPStan static analysis tool
project-stats               # Create simple project stats
psalm                       # Runs Psalm static analysis tool
psalm-github                # Runs Psalm static analysis tool (GitHub)
psalm-shepherd              # Runs Psalm static analysis tool + report results
                            # to shepherd
report-fastest              # Creates clover and JUnit xml from fastest run
run-tests-fastest-phpdbg    # Runs all test via fastest (phpdbg)
run-tests-fastest-php       # Runs all test via fastest (pure PHP)
run-tests-fastest           # Runs all test via fastest (Uses phpdbg if that is
                            # installed)
run-tests-phpdbg            # Runs all tests via phpunit (phpdbg)
run-tests-php               # Runs all tests via phpunit (pure PHP)
run-tests                   # Runs all tests via phpunit (Uses phpdbg if that
                            # is installed)
start                       # Start application in development mode + watch
                            # output
start-build                 # Build containers and start application in
                            # development mode + watch output
stop                        # Stop application containers
update                      # Update composer dependencies
update-bin                  # Update composer bin dependencies
```

### Symfony console [ᐞ](#table-of-contents)

You can list all Symfony console commands via following command:

```bash
./bin/console
```

or

```bash
console
```

### Custom commands [ᐞ](#table-of-contents)

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

#### user:management [ᐞ](#table-of-contents)

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

This command is just a wrapper for following commands:

```bash
./bin/console api-key:create          # To create API key
./bin/console api-key:edit            # To edit API key
./bin/console api-key:change-token    # To change API key token
./bin/console api-key:remove          # To remove API key
./bin/console api-key:list            # To list API keys
```

#### utils:create-date-dimension-entities [ᐞ](#table-of-contents)

Command to create `DateDimension` entities that can be used with date/time
related report queries.

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
