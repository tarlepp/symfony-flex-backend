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
	INSIDE_DOCKER = 1
else ifneq ("$(wildcard /.dockerenv)", "")
	INSIDE_DOCKER = 1
else
	INSIDE_DOCKER = 0
endif

# Global variables that we're using
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
WARNING_HOST = @printf "\033[31mThis command cannot be run inside docker container!\033[39m\n"
WARNING_DOCKER = @printf "\033[31mThis command must be run inside docker container!\nUse 'make bash' command to get shell inside container.\033[39m\n"

.DEFAULT_GOAL := help
.PHONY: help

configuration: ## Prints out application current configuration
ifeq ($(INSIDE_DOCKER), 1)
	@echo | jq -r . ${APPLICATION_CONFIG}
else
	$(WARNING_DOCKER)
endif

help:
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-27s[0m %s\n", $$1, $$2}'

CONSOLE := $(shell which bin/console)
sf_console:
ifndef CONSOLE
	@printf "Run \033[32mcomposer require cli\033[39m to install the Symfony console.\n"
endif

cache-clear: ## Clears the cache
ifdef CONSOLE
	@bin/console cache:clear --no-warmup
else
	@rm -rf var/cache/*
endif
.PHONY: cache-clear

cache-warmup: cache-clear ## Warms up an empty cache
ifdef CONSOLE
	@bin/console cache:warmup
else
	@printf "Cannot warm up the cache (needs symfony/console).\n"
endif
.PHONY: cache-warmup

OPENSSL_BIN := $(shell which openssl)
generate-jwt-keys: ## Generates JWT auth keys
ifndef OPENSSL_BIN
	@printf "\033[31mUnable to generate keys (needs OpenSSL)\033[39m\n"
else ifeq ($(INSIDE_DOCKER), 0)
	$(WARNING_DOCKER)
else
	@echo "\033[32mGenerating RSA keys for JWT\033[39m"
	@mkdir -p config/jwt
	@rm -f ${JWT_SECRET_KEY}
	@rm -f ${JWT_PUBLIC_KEY}
	@openssl genrsa -passout pass:${JWT_PASSPHRASE} -out ${JWT_SECRET_KEY} -aes256 4096
	@openssl rsa -passin pass:${JWT_PASSPHRASE} -pubout -in ${JWT_SECRET_KEY} -out ${JWT_PUBLIC_KEY}
	@chmod 664 ${JWT_SECRET_KEY}
	@chmod 664 ${JWT_PUBLIC_KEY}
	@echo "\033[32mRSA key pair successfully generated\033[39m"
endif

PHPDBG := $(shell which phpdbg)
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
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning test with PhpUnit in single thread (pure PHP)\033[39m"
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml
else
	$(WARNING_DOCKER)
endif

run-tests-phpdbg: ## Runs all tests via phpunit (phpdbg)
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning test with PhpUnit in single thread (phpdbg)\033[39m"
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@phpdbg -qrr ./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml
else
	$(WARNING_DOCKER)
endif

run-tests-fastest-php: ## Runs all test via fastest (pure PHP)
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning tests with liuggio/fastest + PhpUnit in multiple threads (pure PHP)\033[39m"
	@rm -rf build/fastest
	@mkdir -p build/fastest
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@find tests/ -name "*Test.php" | php ./vendor/bin/fastest -v -p 8 -o -b "php ./tests/bootstrap_fastest.php" "php ./vendor/bin/phpunit {} -c phpunit.fastest.xml --coverage-php build/fastest/{n}.cov --log-junit build/fastest/{n}.xml";
else
	$(WARNING_DOCKER)
endif

run-tests-fastest-phpdbg: ## Runs all test via fastest (phpdbg)
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning tests with liuggio/fastest + PhpUnit in multiple threads (phpdbg)\033[39m"
	@rm -rf build/fastest
	@mkdir -p build/fastest
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@find tests/ -name "*Test.php" | php ./vendor/bin/fastest -v -p 8 -o -b "php ./tests/bootstrap_fastest.php" "phpdbg -qrr -d memory_limit=4096M ./vendor/bin/phpunit {} -c phpunit.fastest.xml --coverage-php build/fastest/{n}.cov --log-junit build/fastest/{n}.xml";
else
	$(WARNING_DOCKER)
endif

report-fastest: ## Creates clover and JUnit xml from fastest run
ifeq ($(INSIDE_DOCKER), 1)
	@rm -rf build/logs
	@mkdir -p build/logs
	@./vendor/bin/phpcov merge ./build/fastest/ --clover=./build/logs/clover.xml --html ./build/report/
	@php merge-phpunit-xml.php ./build/fastest/ ./build/logs/junit.xml
else
	$(WARNING_DOCKER)
endif

infection: ## Runs Infection to codebase
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning Infection to codebase (pure PHP)\033[39m"
	@mkdir -p build/infection
	@bin/console cache:clear --env=test
	@./vendor/bin/infection --threads=8 --only-covered --show-mutations --test-framework-options="--testsuite=Functional,Integration,Unit"
else
	$(WARNING_DOCKER)
endif

phpmetrics: ## Generates PhpMetrics static analysis
ifeq ($(INSIDE_DOCKER), 1)
	@mkdir -p build/phpmetrics
	@if [ ! -f build/logs/junit.xml ] ; then \
		printf "\033[32;49mclover.xml not found running tests...\033[39m\n" ; \
		make run-tests or make run-tests-fastests ; \
	fi;
	@echo "\033[32mRunning PhpMetrics\033[39m"
	@php ./vendor/bin/phpmetrics --version
	@./vendor/bin/phpmetrics --junit=build/logs/junit.xml --report-html=build/phpmetrics .
else
	$(WARNING_DOCKER)
endif

phpcs: ## Runs PHP CodeSniffer
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning PhpCodeSniffer\033[39m"
	@php ./vendor/bin/phpcs --version
	@php ./vendor/bin/phpcs --standard=PSR2 --colors -p src tests
else
	$(WARNING_DOCKER)
endif

ecs: ## Runs The Easiest Way to Use Any Coding Standard
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning EasyCodingStandard\033[39m"
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs --clear-cache check src tests
else
	$(WARNING_DOCKER)
endif

ecs-fix: ## Runs The Easiest Way to Use Any Coding Standard to fix issues
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning EasyCodingStandard\033[39m"
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs --clear-cache --fix check src tests
else
	$(WARNING_DOCKER)
endif

phpinsights: ## Runs PHP Insights
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning PHP Insights\033[39m"
	@php -d error_reporting=0 ./vendor/bin/phpinsights analyse --no-interaction --min-quality=100 --min-complexity=85 --min-architecture=100 --min-style=100
else
	$(WARNING_DOCKER)
endif

psalm: ## Runs Psalm static analysis tool
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@mkdir -p build
	@@bin/console cache:clear --env=test
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --report=./build/psalm.json
else
	$(WARNING_DOCKER)
endif

psalm-shepherd: ## Runs Psalm static analysis tool + report results to shepherd
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@mkdir -p build
	@@bin/console cache:clear --env=test
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --report=./build/psalm.json
else
	$(WARNING_DOCKER)
endif

psalm-github: ## Runs Psalm static analysis tool
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@mkdir -p build
	@@bin/console cache:clear --env=test
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --report=./build/psalm.json --output-format=github
else
	$(WARNING_DOCKER)
endif

phpstan: ## Runs PHPStan static analysis tool
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mRunning PHPStan - PHP Static Analysis Tool\033[39m"
	@@bin/console cache:clear --env=test
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan analyze src
else
	$(WARNING_DOCKER)
endif

lint-configuration: ## Lint current defined `application.json` that it contains valid JSON
ifeq ($(INSIDE_DOCKER), 1)
	@php -r "if (!json_decode(file_get_contents('${APPLICATION_CONFIG}'))) { echo \"\033[31mInvalid JSON in configuration file '${APPLICATION_CONFIG}'\033[39m\n\"; exit(1); } else { echo \"\033[32mNo errors in configuration file '${APPLICATION_CONFIG}'\033[39m\n\"; }"
else
	$(WARNING_DOCKER)
endif

lint-yaml: ## Lint config YAML files
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mLinting YAML config files\033[39m"
	@@bin/console lint:yaml config --parse-tags
else
	$(WARNING_DOCKER)
endif

clear-tools: ## Clears all tools depedencies
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mClearing tools dependencies\033[39m"
	@find -type d -name vendor | grep tools | xargs rm -rf
	@echo "\033[32mremember to run 'make update' command after this\033[39m"
else
	$(WARNING_DOCKER)
endif

check-dependencies: ## Checks if any vendor dependency can be updated
ifeq ($(INSIDE_DOCKER), 1)
	@echo "\033[32mChecking vendor dependencies\033[39m"
	@bin/console check-dependencies
else
	$(WARNING_DOCKER)
endif

update: ## Update composer dependencies
ifeq ($(INSIDE_DOCKER), 1)
	@php -d memory_limit=-1 /usr/bin/composer update
else
	$(WARNING_DOCKER)
endif

COMPOSER_BIN := $(shell which composer)
update-bin: ## Update composer bin dependencies
ifeq ($(INSIDE_DOCKER), 1)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all install --no-progress --no-suggest --optimize-autoloader
else
	$(WARNING_DOCKER)
endif

bash: ## Get bash inside PHP container
ifeq ($(INSIDE_DOCKER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose exec php bash
endif

start: ## Start application in development mode
ifeq ($(INSIDE_DOCKER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up
endif

stop: ## Stop application containers
ifeq ($(INSIDE_DOCKER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose down
endif

start-build: ## Start application in development mode and build containers
ifeq ($(INSIDE_DOCKER), 1)
	$(WARNING_HOST)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker-compose up --build
endif

local-configuration: ## Create local configuration files
ifeq ($(INSIDE_DOCKER), 1)
	@cp /app/.env /app/.env.local
	@cp /app/secrets/application.json /app/secrets/application.local.json
	@sed -i "s/application\.json/application\.local\.json/g" .env.local
	@echo "\033[32mLocal configuration created, just edit your new \`secrets/application.local.json\` file for your needs\033[39m"
else
	$(WARNING_DOCKER)
endif

normalize-composer: ## Normalizes `composer.json` content
ifeq ($(INSIDE_DOCKER), 1)
	@composer normalize
else
	$(WARNING_DOCKER)
endif

phploc: ## Runs `phploc` and create json output
ifeq ($(INSIDE_DOCKER), 1)
	@php ./vendor/bin/phploc --log-json=./build/phploc.json ./src
else
	$(WARNING_DOCKER)
endif
