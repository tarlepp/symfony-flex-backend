ifndef APPLICATION_CONFIG
	# Determine which .env file to use
	ifneq ("$(wildcard .env.local)", "")
		include .env.local
	else
		include .env
	endif
endif

ifndef VERBOSE
MAKEFLAGS += --no-print-directory
endif

# Define used JWT keys paths and passphrase
JWT_PUBLIC_KEY=$$(echo | jq -r .JWT_PUBLIC_KEY ${APPLICATION_CONFIG})
JWT_SECRET_KEY=$$(echo | jq -r .JWT_SECRET_KEY ${APPLICATION_CONFIG})
JWT_PASSPHRASE=$$(echo | jq -r .JWT_PASSPHRASE ${APPLICATION_CONFIG})

ifdef GITHUB_WORKFLOW
	INSIDE_DOCKER_CONTAINER = 1
else ifneq ("$(wildcard /.dockerenv)", "")
	INSIDE_DOCKER_CONTAINER = 1
else
	INSIDE_DOCKER_CONTAINER = 0
endif

# Global variables that we're using
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
CONSOLE := $(shell which bin/console)
OPENSSL_BIN := $(shell which openssl)
PHPDBG := $(shell which phpdbg)
COMPOSER_BIN := $(shell which composer)
DOCKER := $(shell which docker)
CONTAINER_PREFIX := 'symfony-backend' # Check your `docker-compose.yml` file `container_name` property for this one
CONTAINER_COUNT := 5 # Check your `docker-compose.yml` file service count for this one

ifdef DOCKER
	RUNNING_SOME_CONTAINERS := $(shell docker ps -f name=$(CONTAINER_PREFIX) | grep -c $(CONTAINER_PREFIX))
	TEMP := $(shell docker ps -f name=$(CONTAINER_PREFIX) | grep -c $(CONTAINER_PREFIX) | grep $(CONTAINER_COUNT))
else
	RUNNING_SOME_CONTAINERS := 0;
	TEMP := 0
endif

ifeq ($(INSIDE_DOCKER_CONTAINER)$(TEMP), $(addprefix 0, $(CONTAINER_COUNT)))
	RUNNING_ALL_CONTAINERS = 1
else
	RUNNING_ALL_CONTAINERS = 0;
endif

ERROR_DOCKER = @printf "\033[31mSeems like that all necessary Docker containers are not running atm.\nCheck logs for detailed information about the reason of this\033[39m\n"
NOTICE_HOST = @printf "\033[33mRunning command from host machine by using 'docker-compose exec' command\033[39m\n"
WARNING_HOST = @printf "\033[31mThis command cannot be run inside docker container!\033[39m\n"
WARNING_DOCKER = @printf "\033[31mThis command must be run inside specified docker container and it's not\nrunning atm. Use \`make start\` and/or \`make daemon\` command to get\nnecessary container(s) running and after that run this command again.\033[39m\n"
WARNING_DOCKER_RUNNING = @printf "\033[31mDocker is already running - you cannot execute this command multiple times\033[39m\n"
ALL_DONE = @printf $(_TITLE) "All done" "Have a nice day"

_WARNING := "\033[33m[%s]\033[0m %s\n" # Yellow text for "printf"
_TITLE := "\033[32m[%s]\033[0m %s\n" # Green text for "printf"
_ERROR := "\033[31m[%s]\033[0m %s\n" # Red text for "printf"

info_msg := Message
info:
	$(info_msg)

.DEFAULT_GOAL := help

help:
	@echo "\033[34mList of available commands:\033[39m"
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-27s[0m %s\n", $$1, $$2}'

configuration: ## Prints out application current configuration
configuration: info_msg := @printf $(_TITLE) "OK" "Application configuration:"
configuration: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo | jq -r . ${APPLICATION_CONFIG}
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make configuration
	$(ALL_DONE)
endif

cache-clear: ## Clears the cache
cache-clear: info_msg := @printf $(_TITLE) "OK" "Clearing application cache"
cache-clear: info
ifdef CONSOLE
	@bin/console cache:clear --no-warmup
	$(ALL_DONE)
else
	@rm -rf var/cache/*
endif

cache-warmup: cache-clear ## Warms up an empty cache
cache-warmup: info_msg := @printf $(_TITLE) "OK" "Warming up cache"
cache-warmup: info
ifdef CONSOLE
	@bin/console cache:warmup
	$(ALL_DONE)
else
	@printf "Cannot warm up the cache (needs symfony/console).\n"
endif

generate-jwt-keys: ## Generates JWT auth keys
generate-jwt-keys: info_msg := @printf $(_TITLE) "OK" "Generating JWT authentication keys"
generate-jwt-keys: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mGenerating RSA keys for JWT\033[39m"
	@mkdir -p config/jwt
	@rm -f ${JWT_SECRET_KEY}
	@rm -f ${JWT_PUBLIC_KEY}
	@openssl genrsa -passout pass:${JWT_PASSPHRASE} -out ${JWT_SECRET_KEY} -aes256 4096
	@openssl rsa -passin pass:${JWT_PASSPHRASE} -pubout -in ${JWT_SECRET_KEY} -out ${JWT_PUBLIC_KEY}
	@chmod 644 ${JWT_SECRET_KEY}
	@chmod 644 ${JWT_PUBLIC_KEY}
	@echo "\033[32mRSA key pair successfully generated\033[39m"
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make generate-jwt-keys
	$(ALL_DONE)
endif

run-tests: ## Runs all tests via phpunit (Uses phpdbg if that is installed)
ifndef PHPDBG
	@${MAKE} run-tests-php
else
	@${MAKE} run-tests-phpdbg
endif

run-tests-fastest: ## Runs all test via fastest (Uses phpdbg if that is installed)
ifndef PHPDBG
	@${MAKE} run-tests-fastest-php
else
	@${MAKE} run-tests-fastest-phpdbg
endif

run-tests-php: ## Runs all tests via phpunit (pure PHP)
run-tests-php: info_msg := @printf $(_TITLE) "OK" "Running tests via phpunit (pure PHP)"
run-tests-php: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make run-tests-php
	$(ALL_DONE)
endif

run-tests-phpdbg: ## Runs all tests via phpunit (phpdbg)
run-tests-phpdbg: info_msg := @printf $(_TITLE) "OK" "Running test with PhpUnit in single thread (phpdbg)"
run-tests-phpdbg: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@phpdbg -qrr ./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make run-tests-phpdbg
	$(ALL_DONE)
endif

run-tests-fastest-php: ## Runs all test via fastest (pure PHP)
run-tests-fastest-php: info_msg := @printf $(_TITLE) "OK" "Running tests with liuggio/fastest + PhpUnit in multiple threads (pure PHP)"
run-tests-fastest-php: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@rm -rf build/fastest
	@mkdir -p build/fastest
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@find tests/ -name "*Test.php" | php ./vendor/bin/fastest -v -p 8 -o -b "php ./tests/bootstrap_fastest.php" "php ./vendor/bin/phpunit {} -c phpunit.fastest.xml --coverage-php build/fastest/{n}.cov --log-junit build/fastest/{n}.xml";
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make run-tests-fastest-php
	$(ALL_DONE)
endif

run-tests-fastest-phpdbg: ## Runs all test via fastest (phpdbg)
run-tests-fastest-phpdbg: info_msg := @printf $(_TITLE) "OK" "Running tests with liuggio/fastest + PhpUnit in multiple threads (phpdbg)"
run-tests-fastest-phpdbg: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@rm -rf build/fastest
	@mkdir -p build/fastest
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@find tests/ -name "*Test.php" | php ./vendor/bin/fastest -v -p 8 -o -b "php ./tests/bootstrap_fastest.php" "phpdbg -qrr -d memory_limit=4096M ./vendor/bin/phpunit {} -c phpunit.fastest.xml --coverage-php build/fastest/{n}.cov --log-junit build/fastest/{n}.xml";
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make run-tests-fastest-phpdbg
	$(ALL_DONE)
endif

report-fastest: ## Creates clover and JUnit xml from fastest run
report-fastest: info_msg := @printf $(_TITLE) "OK" "Creating clover and JUnit xml from fastest run"
report-fastest: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@rm -rf build/logs
	@mkdir -p build/logs
	@./vendor/bin/phpcov merge ./build/fastest/ --clover=./build/logs/clover.xml --html ./build/report/
	@php merge-phpunit-xml.php ./build/fastest/ ./build/logs/junit.xml
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make report-fastest
	$(ALL_DONE)
endif

infection: ## Runs Infection to codebase
infection: info_msg := @printf $(_TITLE) "OK" "Running Infection to codebase (pure PHP)"
infection: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build/infection
	@bin/console cache:clear --env=test
	@./vendor/bin/infection --threads=8 --only-covered --show-mutations --test-framework-options="--testsuite=Functional,Integration,Unit"
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make infection
	$(ALL_DONE)
endif

phpmetrics: ## Generates PhpMetrics static analysis
phpmetrics: info_msg := @printf $(_TITLE) "OK" "Generating PhpMetrics static analysis"
phpmetrics: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build/phpmetrics
	@if [ ! -f build/logs/junit.xml ] ; then \
		printf $(_WARNING) "Warning" "clover.xml not found, need to run tests before this..." ; \
		make -s run-tests; \
		printf $(_TITLE) "OK" "Generating PhpMetrics static analysis" ; \
	fi;
	@php ./vendor/bin/phpmetrics --version
	@./vendor/bin/phpmetrics --junit=build/logs/junit.xml --report-html=build/phpmetrics .
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phpmetrics
endif

phpcs: ## Runs PHP CodeSniffer
phpcs: info_msg := @printf $(_TITLE) "OK" "Running PHP CodeSniffer"
phpcs: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phpcs --version
	@php ./vendor/bin/phpcs --standard=PSR2 --colors -p src tests
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phpcs
	$(ALL_DONE)
endif

ecs: ## Runs The Easiest Way to Use Any Coding Standard
ecs: info_msg := @printf $(_TITLE) "OK" "Running EasyCodingStandard"
ecs: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs --clear-cache check src tests
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make ecs
	$(ALL_DONE)
endif

ecs-fix: ## Runs The Easiest Way to Use Any Coding Standard to fix issues
ecs-fix: info_msg := @printf $(_TITLE) "OK" "Running EasyCodingStandard with --fix option"
ecs-fix: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs --clear-cache --fix check src tests
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make ecs-fix
	$(ALL_DONE)
endif

phpinsights: ## Runs PHP Insights
phpinsights: info_msg := @printf $(_TITLE) "OK" "Running PHP Insights"
phpinsights: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -d error_reporting=0 ./vendor/bin/phpinsights analyse --no-interaction --min-quality=95 --min-complexity=85 --min-architecture=100 --min-style=100
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phpinsights
	$(ALL_DONE)
endif

phplint: ## Runs PHPLint
phplint: info_msg := @printf $(_TITLE) "OK" "Running PHPLint"
phplint: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phplint --no-cache
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phplint
	$(ALL_DONE)
endif

psalm: ## Runs Psalm static analysis tool
psalm: info_msg := @printf $(_TITLE) "OK" "Running Psalm - A static analysis tool for PHP"
psalm: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --show-info=true --report=./build/psalm.json
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make psalm
	$(ALL_DONE)
endif

psalm-shepherd: ## Runs Psalm static analysis tool + report results to shepherd
psalm-shepherd: info_msg := @printf $(_TITLE) "OK" "Running Psalm - A static analysis tool for PHP"
psalm-shepherd: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --show-info=true --report=./build/psalm.json
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make psalm-shepherd
	$(ALL_DONE)
endif

psalm-github: ## Runs Psalm static analysis tool (GitHub)
psalm-github: info_msg := @printf $(_TITLE) "OK" "Running Psalm - A static analysis tool for PHP"
psalm-github: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --show-info=true --report=./build/psalm.json --output-format=github
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make psalm-github
	$(ALL_DONE)
endif

phpstan: ## Runs PHPStan static analysis tool
phpstan: info_msg := @printf $(_TITLE) "OK" "Running PHPStan - PHP Static Analysis Tool"
phpstan: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@@bin/console cache:clear
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan -v
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phpstan
	$(ALL_DONE)
endif

phpstan-github: ## Runs PHPStan static analysis tool (GitHub)
phpstan-github: info_msg := @printf $(_TITLE) "OK" "Running PHPStan - PHP Static Analysis Tool"
phpstan-github: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@@bin/console cache:clear
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan -v --error-format=github
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phpstan
	$(ALL_DONE)
endif

lint-configuration: ## Lint current defined `application.json` that it contains valid JSON
lint-configuration: info_msg := @printf $(_TITLE) "OK" "Linting application current configuration"
lint-configuration: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -r "if (!json_decode(file_get_contents('${APPLICATION_CONFIG}'))) { echo \"\033[31mInvalid JSON in configuration file '${APPLICATION_CONFIG}'\033[39m\n\"; exit(1); } else { echo \"\033[32mNo errors in configuration file '${APPLICATION_CONFIG}'\033[39m\n\"; }"
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make lint-configuration
	$(ALL_DONE)
endif

lint-yaml: ## Lint config YAML files
lint-yaml: info_msg := @printf $(_TITLE) "OK" "Linting YAML config files"
lint-yaml: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@@bin/console lint:yaml config --parse-tags
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make lint-yaml
	$(ALL_DONE)
endif

clear: ## Clean vendor and tool dependencies
clear: info_msg := @printf $(_TITLE) "OK" "Clearing vendor dependencies"
clear: info
	@rm -rf vendor
	@${MAKE} clear-tools

clear-tools: ## Clears all tools dependencies
clear-tools: info_msg := @printf $(_TITLE) "OK" "Clearing tools dependencies"
clear-tools: info
	@find -type d -name vendor | grep tools | xargs rm -rf
	@printf $(_TITLE) "OK" "remember to restart your containers after this"
	$(ALL_DONE)

check-dependencies-latest: ## Checks if any vendor dependency can be updated (latest versions)
check-dependencies-latest: info_msg := @printf $(_TITLE) "OK" "Checking vendor dependencies (latest)"
check-dependencies-latest: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bin/console check-dependencies
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-dependencies-latest
	$(ALL_DONE)
endif

check-dependencies-minor: ## Checks if any vendor dependency can be updated (only minor versions)
check-dependencies-minor: info_msg := @printf $(_TITLE) "OK" "Checking vendor dependencies (minor)"
check-dependencies-minor: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bin/console check-dependencies --minor
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-dependencies-minor
	$(ALL_DONE)
endif

check-dependencies-patch: ## Checks if any vendor dependency can be updated (only patch versions)
check-dependencies-patch: info_msg := @printf $(_TITLE) "OK" "Checking vendor dependencies (patch)"
check-dependencies-patch: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bin/console check-dependencies --patch
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-dependencies-patch
	$(ALL_DONE)
endif

check-licenses: ## Check vendor licenses
check-licenses: info_msg := @printf $(_TITLE) "OK" "Checking vendor licenses"
check-licenses: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@composer license | awk '{ print $$3 }' | sort | uniq -c | sort -rn
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-licenses
	$(ALL_DONE)
endif

update: ## Update composer dependencies
update: info_msg := @printf $(_TITLE) "OK" "Updating composer dependencies"
update: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -d memory_limit=-1 /usr/bin/composer update --with-all-dependencies --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make update
	$(ALL_DONE)
endif

update-bin: ## Update composer bin dependencies
update-bin: info_msg := @printf $(_TITLE) "OK" "Updating composer bin dependencies"
update-bin: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all update --no-progress --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make update-bin
	$(ALL_DONE)
endif

install-bin: ## Install composer bin dependencies
install-bin: info_msg := @printf $(_TITLE) "OK" "Installing composer bin dependencies"
install-bin: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all install --no-progress --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make install-bin
	$(ALL_DONE)
endif

bash: ## Get bash inside PHP container
bash: info_msg := @printf $(_TITLE) "OK" "Opening bash shell to PHP container"
bash: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php bash
endif

fish: ## Get fish inside PHP container
fish: info_msg := @printf $(_TITLE) "OK" "Opening fish shell to PHP container"
fish: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php fish
endif

logs: ## Show logs from all containers
logs: info_msg := @printf $(_TITLE) "OK" "Showing logs from all the containers"
logs: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose logs --follow php nginx mariadb
endif

daemon: ## Start application in development mode in background
daemon: info_msg := @printf $(_TITLE) "OK" "Starting application in development mode in background"
daemon: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER_RUNNING)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up --detach
	@printf $(_TITLE) "OK" "Containers are running background, check logs for detailed information!"
endif

daemon-build: ## Build containers and start application in development mode in background
daemon-build: info_msg := @printf $(_TITLE) "OK" "Building containers and starting application in development mode in background"
daemon-build: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER_RUNNING)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up --build --detach
	@printf $(_TITLE) "OK" "Containers are built and those are running background, check logs for detailed information!"
endif

stop: ## Stop application containers
stop: info_msg := @printf $(_TITLE) "OK" "Stopping application containers"
stop: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose down
	$(ALL_DONE)
endif

start: ## Start application in development mode + watch output
start: info_msg := @printf $(_TITLE) "OK" "Starting application in development mode"
start: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER_RUNNING)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up
	$(ALL_DONE)
endif

start-build: ## Build containers and start application in development mode + watch output
start-build: info_msg := @printf $(_TITLE) "OK" "Building containers and starting application in development mode"
start-build: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER_RUNNING)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up --build
	$(ALL_DONE)
endif

local-configuration: ## Create local configuration files
local-configuration: info_msg := @printf $(_TITLE) "OK" "Creating local configuration"
local-configuration: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@cp /app/.env /app/.env.local
	@cp /app/secrets/application.json /app/secrets/application.local.json
	@sed -i "s/application\.json/application\.local\.json/g" .env.local
	@printf $(_TITLE) "OK"  "Local configuration created, just edit your new \`secrets/application.local.json\` file for your needs"
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make local-configuration
	$(ALL_DONE)
endif

composer-normalize: ## Normalizes `composer.json` file content
composer-normalize: info_msg := @printf $(_TITLE) "OK" "Normalizing `composer.json` file contents"
composer-normalize: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@composer normalize
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make composer-normalize
	$(ALL_DONE)
endif

composer-validate: ## Validate `composer.json` file content
composer-validate: info_msg := @printf $(_TITLE) "OK" "Validating `composer.json` file contents"
composer-validate: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@composer validate --no-check-version && ([ $$? -eq 0 ] && echo "\033[32mGood news, your \`composer.json\` file is valid\033[39m")
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make composer-validate
	$(ALL_DONE)
endif

composer-require-checker: ## Check the defined dependencies against your code
composer-require-checker: info_msg := @printf $(_TITLE) "OK" "Checking defined dependecies against project code"
composer-require-checker: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@XDEBUG_MODE=off php /app/vendor/bin/composer-require-checker
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make composer-require-checker
	$(ALL_DONE)
endif

composer-unused: ## Show unused packages by scanning and comparing package namespaces against your source
composer-unused: info_msg := @printf $(_TITLE) "OK" "Checking unused packages"
composer-unused: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@XDEBUG_MODE=off php /app/vendor/bin/composer-unused
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make composer-unused
	$(ALL_DONE)
endif

phploc: ## Runs `phploc` and create json output
phploc: info_msg := @printf $(_TITLE) "OK" "Running PHPLOC"
phploc: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phploc --log-json=./build/phploc.json ./src
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phploc
	$(ALL_DONE)
endif

check-security: ## Checks that application doesn't have installed dependencies with known security vulnerabilities
check-security: info_msg := @printf $(_TITLE) "OK" "Checking installed packages for known security vulnerabilities"
check-security: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@composer update --dry-run --no-plugins roave/security-advisories
	@if which local-php-security-checker; then local-php-security-checker --update-cache && local-php-security-checker; fi
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-security
	$(ALL_DONE)
endif

docker-prune: info_msg := @printf $(_ERROR) "Caution !!!" "Killing all running containers and pruning all docker stuff"
docker-prune: info
docker-prune: ## Kill all running containers and prune all docker stuff
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else
	@echo -n "\033[31mAre you sure that you want to kill ALL running containers and prune ALL docker data? [y/N]\033[39m\n " && read ans && if [ $${ans:-'N'} = 'y' ]; then \
		printf $(_TITLE) "OK" "Continuing with full system prune"; \
		docker kill $$(docker ps -q) 2> /dev/null && docker system prune --all --volumes --force; \
		printf $(_TITLE) "All done" "Have a nice day"; \
	fi
endif

docker-kill-containers: ## Kill all running docker containers
docker-kill-containers: info_msg := @printf $(_TITLE) "OK" "Killing running docker containers"
docker-kill-containers: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker kill $$(docker ps -q)
	$(ALL_DONE)
endif

docker-remove-containers: ## Remove all docker containers
docker-remove-containers: info_msg := @printf $(_TITLE) "OK" "Removing docker containers"
docker-remove-containers: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker rm $$(docker ps -a -q)
	$(ALL_DONE)
endif

docker-remove-images: ## Remove all docker images
docker-remove-images: info_msg := @printf $(_TITLE) "OK" "Removing docker images"
docker-remove-images: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker rmi $$(docker images -q)
	$(ALL_DONE)
endif

generate-ssl-cert: ## Generate self signed SSL certificates
ifeq ($(INSIDE_DOCKER), 1)
	$(WARNING_HOST)
else
	@echo "\033[32mGenerating self signed SSL certificates\033[39m"
	@cd docker/nginx/ssl && ./create-keys.sh
endif

project-stats: ## Create simple project stats
project-stats: info_msg := @printf $(_TITLE) "OK" "Creating simple project stats"
project-stats: info
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@./scripts/project-stats.sh
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make project-stats
	$(ALL_DONE)
endif
