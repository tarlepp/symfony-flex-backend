ifndef APPLICATION_CONFIG
	# Determine which .env file to use
	ifneq ("$(wildcard .env.local)", "")
		include .env.local
	else
		include .env
	endif
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

.DEFAULT_GOAL := help

help:
	@echo "\033[34mList of available commands:\033[39m"
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-27s[0m %s\n", $$1, $$2}'

configuration: ## Prints out application current configuration
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo | jq -r . ${APPLICATION_CONFIG}
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make configuration
endif

cache-clear: ## Clears the cache
ifdef CONSOLE
	@bin/console cache:clear --no-warmup
else
	@rm -rf var/cache/*
endif

cache-warmup: cache-clear ## Warms up an empty cache
ifdef CONSOLE
	@bin/console cache:warmup
else
	@printf "Cannot warm up the cache (needs symfony/console).\n"
endif

generate-jwt-keys: ## Generates JWT auth keys
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
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning test with PhpUnit in single thread (pure PHP)\033[39m"
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
endif

run-tests-phpdbg: ## Runs all tests via phpunit (phpdbg)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning test with PhpUnit in single thread (phpdbg)\033[39m"
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
endif

run-tests-fastest-php: ## Runs all test via fastest (pure PHP)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning tests with liuggio/fastest + PhpUnit in multiple threads (pure PHP)\033[39m"
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
endif

run-tests-fastest-phpdbg: ## Runs all test via fastest (phpdbg)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning tests with liuggio/fastest + PhpUnit in multiple threads (phpdbg)\033[39m"
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
endif

report-fastest: ## Creates clover and JUnit xml from fastest run
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
endif

infection: ## Runs Infection to codebase
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning Infection to codebase (pure PHP)\033[39m"
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
endif

phpmetrics: ## Generates PhpMetrics static analysis
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build/phpmetrics
	@if [ ! -f build/logs/junit.xml ] ; then \
		printf "\033[32;49mclover.xml not found running tests...\033[39m\n" ; \
		make run-tests or make run-tests-fastests ; \
	fi;
	@echo "\033[32mRunning PhpMetrics\033[39m"
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
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning PhpCodeSniffer\033[39m"
	@php ./vendor/bin/phpcs --version
	@php ./vendor/bin/phpcs --standard=PSR2 --colors -p src tests
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phpcs
endif

ecs: ## Runs The Easiest Way to Use Any Coding Standard
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning EasyCodingStandard\033[39m"
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs --clear-cache check src tests
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make ecs
endif

ecs-fix: ## Runs The Easiest Way to Use Any Coding Standard to fix issues
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning EasyCodingStandard\033[39m"
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs --clear-cache --fix check src tests
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make ecs-fix
endif

phpinsights: ## Runs PHP Insights
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning PHP Insights\033[39m"
	@php -d error_reporting=0 ./vendor/bin/phpinsights analyse --no-interaction --min-quality=100 --min-complexity=85 --min-architecture=100 --min-style=100
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phpinsights
endif

phplint: ## Runs PHPLint
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning PHPLint\033[39m"
	@php ./vendor/bin/phplint --no-cache
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phplint
endif

psalm: ## Runs Psalm static analysis tool
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --report=./build/psalm.json
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make psalm
endif

psalm-shepherd: ## Runs Psalm static analysis tool + report results to shepherd
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --report=./build/psalm.json
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make psalm-shepherd
endif

psalm-github: ## Runs Psalm static analysis tool (GitHub)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --report=./build/psalm.json --output-format=github
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make psalm-github
endif

phpstan: ## Runs PHPStan static analysis tool
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning PHPStan - PHP Static Analysis Tool\033[39m"
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
endif

phpstan-github: ## Runs PHPStan static analysis tool (GitHub)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning PHPStan - PHP Static Analysis Tool\033[39m"
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
endif

lint-configuration: ## Lint current defined `application.json` that it contains valid JSON
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -r "if (!json_decode(file_get_contents('${APPLICATION_CONFIG}'))) { echo \"\033[31mInvalid JSON in configuration file '${APPLICATION_CONFIG}'\033[39m\n\"; exit(1); } else { echo \"\033[32mNo errors in configuration file '${APPLICATION_CONFIG}'\033[39m\n\"; }"
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make lint-configuration
endif

lint-yaml: ## Lint config YAML files
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mLinting YAML config files\033[39m"
	@@bin/console lint:yaml config --parse-tags
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make lint-yaml
endif

clear-tools: ## Clears all tools dependencies
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mClearing tools dependencies\033[39m"
	@find -type d -name vendor | grep tools | xargs rm -rf
	@echo "\033[32mremember to run 'make update' command after this\033[39m"
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make clear-tools
endif

check-dependencies-latest: ## Checks if any vendor dependency can be updated (latest versions)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mChecking vendor dependencies (latest)\033[39m"
	@bin/console check-dependencies
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-dependencies-latest
endif

check-dependencies-minor: ## Checks if any vendor dependency can be updated (only minor versions)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mChecking vendor dependencies (minor)\033[39m"
	@bin/console check-dependencies --minor
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-dependencies-minor
endif

check-licenses: ## Check vendor licenses
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mChecking vendor licenses\033[39m"
	@composer license | awk '{ print $$3 }' | sort | uniq -c | sort -rn
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-licenses
endif

update: ## Update composer dependencies
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -d memory_limit=-1 /usr/bin/composer update --with-all-dependencies --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make update
endif

update-bin: ## Update composer bin dependencies
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all update --no-progress --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make update-bin
endif

install-bin: ## Install composer bin dependencies
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all install --no-progress --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make install-bin
endif

bash: ## Get bash inside PHP container
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php bash
endif

logs: ## Show logs from all containers
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
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER_RUNNING)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up --detach
	@printf "\033[32mContainers are running background, check logs for detailed information!\033[39m\n"
endif

daemon-build: ## Build containers and start application in development mode in background
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER_RUNNING)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up --build --detach
	@printf "\033[32mContainers are built and those are running background, check logs for detailed information!\033[39m\n"
endif

stop: ## Stop application containers
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose down
endif

start: ## Start application in development mode + watch output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER_RUNNING)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up
endif

start-build: ## Build containers and start application in development mode + watch output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(WARNING_HOST)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER_RUNNING)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up --build
endif

local-configuration: ## Create local configuration files
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@cp /app/.env /app/.env.local
	@cp /app/secrets/application.json /app/secrets/application.local.json
	@sed -i "s/application\.json/application\.local\.json/g" .env.local
	@echo "\033[32mLocal configuration created, just edit your new \`secrets/application.local.json\` file for your needs\033[39m"
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make local-configuration
endif

composer-normalize: ## Normalizes `composer.json` file content
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@composer normalize
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make composer-normalize
endif

composer-validate: ## Validate `composer.json` file content
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@composer validate --no-check-version && ([ $$? -eq 0 ] && echo "\033[32mGood news, your \`composer.json\` file is valid\033[39m")
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make composer-validate
endif

composer-require-checker: ## Check the defined dependencies against your code
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@XDEBUG_MODE=off php /app/vendor/bin/composer-require-checker
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make composer-require-checker
endif

composer-unused: ## Show unused packages by scanning and comparing package namespaces against your source
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@XDEBUG_MODE=off php /app/vendor/bin/composer-unused
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make composer-unused
endif

phploc: ## Runs `phploc` and create json output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phploc --log-json=./build/phploc.json ./src
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make phploc
endif

check-security: ## Checks that application doesn't have installed dependencies with known security vulnerabilities
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@printf "\033[33mChecking installed packages with 'roave/security-advisories' library\033[39m\n"
	@composer update --dry-run --no-plugins roave/security-advisories
	@if which local-php-security-checker; then local-php-security-checker --update-cache && local-php-security-checker; fi
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php make check-security
endif
