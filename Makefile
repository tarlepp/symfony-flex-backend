ifndef APPLICATION_CONFIG
	# Determine which .env file to use
	ifneq ("$(wildcard .env.local)","")
		include .env.local
	else
		include .env
	endif
endif

# Define used JWT keys paths and passphrase
JWT_PUBLIC_KEY=$$(echo | jq -r .JWT_PUBLIC_KEY ${APPLICATION_CONFIG})
JWT_SECRET_KEY=$$(echo | jq -r .JWT_SECRET_KEY ${APPLICATION_CONFIG})
JWT_PASSPHRASE=$$(echo | jq -r .JWT_PASSPHRASE ${APPLICATION_CONFIG})

config:
	@echo | jq -r . ${APPLICATION_CONFIG}

.DEFAULT_GOAL := help
.PHONY: help
help:
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-27s[0m %s\n", $$1, $$2}'

###> symfony/framework-bundle ###
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

serve_as_sf: sf_console
ifndef CONSOLE
	@${MAKE} serve_as_php
endif
	@bin/console list | grep server:start > /dev/null || ${MAKE} serve_as_php
	@bin/console server:start

	@printf "Quit the server with \033[32;49mbin/console server:stop\033[39m\n"

serve_as_php:
	@printf "\033[32;49mServer listening on http://127.0.0.1:8000\033[39m\n"
	@printf "Quit the server with CTRL-C.\n"
	@printf "Run \033[32mcomposer require symfony/web-server-bundle\033[39m for a better web server.\n"
	php -S 127.0.0.1:8000 -t public

serve: ## Runs a local web server
	@${MAKE} serve_as_sf
.PHONY: sf_console serve serve_as_sf serve_as_php
###< symfony/framework-bundle ###

###> lexik/jwt-authentication-bundle ###
OPENSSL_BIN := $(shell which openssl)
generate-jwt-keys: ## Generates JWT auth keys
ifndef OPENSSL_BIN
	$(error "Unable to generate keys (needs OpenSSL)")
endif
	@echo "\033[32mGenerating RSA keys for JWT\033[39m"
	@mkdir -p config/jwt
	@rm -f ${JWT_SECRET_KEY}
	@rm -f ${JWT_PUBLIC_KEY}
	@openssl genrsa -passout pass:${JWT_PASSPHRASE} -out ${JWT_SECRET_KEY} -aes256 4096
	@openssl rsa -passin pass:${JWT_PASSPHRASE} -pubout -in ${JWT_SECRET_KEY} -out ${JWT_PUBLIC_KEY}
	@chmod 664 ${JWT_SECRET_KEY}
	@chmod 664 ${JWT_PUBLIC_KEY}
	@echo "\033[32mRSA key pair successfully generated\033[39m"
###< lexik/jwt-authentication-bundle ###

###> phpunit ###
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
	@echo "\033[32mRunning test with PhpUnit in single thread (pure PHP)\033[39m"
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml

run-tests-phpdbg: ## Runs all tests via phpunit (phpdbg)
	@echo "\033[32mRunning test with PhpUnit in single thread (phpdbg)\033[39m"
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@phpdbg -qrr ./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml

run-tests-fastest-php: ## Runs all test via fastest (pure PHP)
	@echo "\033[32mRunning tests with liuggio/fastest + PhpUnit in multiple threads (pure PHP)\033[39m"
	@rm -rf build/fastest
	@mkdir -p build/fastest
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@find tests/ -name "*Test.php" | php ./vendor/bin/fastest -v -p 8 -o -b "php ./tests/bootstrap_fastest.php" "php ./vendor/bin/phpunit {} -c phpunit.fastest.xml --coverage-php build/fastest/{n}.cov --log-junit build/fastest/{n}.xml";

run-tests-fastest-phpdbg: ## Runs all test via fastest (phpdbg)
	@echo "\033[32mRunning tests with liuggio/fastest + PhpUnit in multiple threads (phpdbg)\033[39m"
	@rm -rf build/fastest
	@mkdir -p build/fastest
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@find tests/ -name "*Test.php" | php ./vendor/bin/fastest -v -p 8 -o -b "php ./tests/bootstrap_fastest.php" "phpdbg -qrr -d memory_limit=4096M ./vendor/bin/phpunit {} -c phpunit.fastest.xml --coverage-php build/fastest/{n}.cov --log-junit build/fastest/{n}.xml";

report-fastest: ## Creates clover and JUnit xml from fastest run
	@rm -rf build/logs
	@mkdir -p build/logs
	@./vendor/bin/phpcov merge ./build/fastest/ --clover=./build/logs/clover.xml --html ./build/report/
	@php merge-phpunit-xml.php ./build/fastest/ ./build/logs/junit.xml

###> infection ###
infection: ## Runs Infection to codebase
	@echo "\033[32mRunning Infection to codebase (pure PHP)\033[39m"
	@mkdir -p build/infection
	@bin/console cache:clear --env=test
	@./vendor/bin/infection --threads=8 --only-covered --show-mutations --test-framework-options="--testsuite=Functional,Integration,Unit"
###< infection ###

###> phpmetrics ###
phpmetrics: ## Generates PhpMetrics static analysis
	@mkdir -p build/phpmetrics
	@if [ ! -f build/logs/junit.xml ] ; then \
		printf "\033[32;49mclover.xml not found running tests...\033[39m\n" ; \
		make run-tests or make run-tests-fastests ; \
	fi;
	@echo "\033[32mRunning PhpMetrics\033[39m"
	@php ./vendor/bin/phpmetrics --version
	@./vendor/bin/phpmetrics --junit=build/logs/junit.xml --report-html=build/phpmetrics .
###< phpmetrics ###

###> phpcs ###
phpcs: ## Runs PHP CodeSniffer
	@echo "\033[32mRunning PhpCodeSniffer\033[39m"
	@php ./vendor/bin/phpcs --version
	@php ./vendor/bin/phpcs --standard=PSR2 --colors -p src
###< phpcs ###

###> ecs ###
ecs: ## Runs The Easiest Way to Use Any Coding Standard
	@echo "\033[32mRunning EasyCodingStandard\033[39m"
	@php -d error_reporting=0 ./vendor/bin/ecs --clear-cache check src

ecs-fix: ## Runs The Easiest Way to Use Any Coding Standard to fix issues
	@echo "\033[32mRunning EasyCodingStandard\033[39m"
	@php -d error_reporting=0 ./vendor/bin/ecs --clear-cache --fix check src
###< ecs ###

###> phpinsights ###
phpinsights: ## Runs PHP Insights
	@echo "\033[32mRunning PHP Insights\033[39m"
	@php -d error_reporting=0 ./vendor/bin/phpinsights analyse --no-interaction --min-quality=100 --min-complexity=85 --min-architecture=100 --min-style=100
###< phpinsights ###

###> psalm ###
psalm: ## Runs Psalm static analysis tool
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@mkdir -p build
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --report=./build/psalm.json
###< psalm ###

###> psalm with shepherd ###
psalm-shepherd: ## Runs Psalm static analysis tool + report results to shepherd
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@mkdir -p build
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --report=./build/psalm.json
###< psalm ###

###> phpstan ###
phpstan: ## Runs PHPStan static analysis tool
	@echo "\033[32mRunning PHPStan - PHP Static Analysis Tool\033[39m"
	@@bin/console cache:clear --env=test
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan analyze src
###< phpstan ###

###> lint configuration ###
lint-configuration: ## Lint current defined `application.json` that it contains valid JSON
	@php -r "if (!json_decode(file_get_contents('${APPLICATION_CONFIG}'))) { echo \"\033[31mInvalid JSON in configuration file '${APPLICATION_CONFIG}'\033[39m\n\"; exit(1);}"
###< lint configuration ###

###> lint yaml ###
lint-yaml: ## Lint config YAML files
	@echo "\033[32mLinting YAML config files\033[39m"
	@@bin/console lint:yaml config --parse-tags
###< lint yaml ###

###> clear tools ###
clear-tools: ## Clears all tools depedencies
	@echo "\033[32mClearing tools dependencies\033[39m"
	@find -type d -name vendor | grep tools | xargs rm -rf
	@echo "\033[32mremember to run 'make update' command after this\033[39m"
###< clear tools ###

###> check dependencies ###
check-dependencies: ## Checks if any vendor dependency can be updated
	@echo "\033[32mChecking vendor dependencies\033[39m"
	@bin/console check-dependencies
###< check dependencies ###

###> update composer dependencies ###
update: ## Update composer dependencies
	@php -d memory_limit=-1 /usr/bin/composer update
###< update composer dependencies ###

###> update composer bin dependencies ###
COMPOSER_BIN := $(shell which composer)
update-bin: ## Update composer bin dependencies
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all install --no-progress --no-suggest --optimize-autoloader
###< update composer bin dependencies ###

###> get bash inside php container ###
bash: ## Get bash inside PHP container
	@docker-compose exec php bash
###< get bash inside php container ###

###> Start application in development mode ###
start: ## Start application in development mode
	@docker-compose up
###< Start application in development mode ###

###> Stop application containers ###
stop: ## Stop application containers
	@docker-compose down
###< Stop application containers ###

###> Start application in development mode and build containers ###
start-build: ## Start application in development mode and build containers
	@docker-compose up --build
###< Start application in development mode and build containers ###

###> Create local configuration ###
local-configuration: ## Create local configuration files
	@cp /app/.env /app/.env.local
	@cp /app/secrets/application.json /app/secrets/application.local.json
	@sed -i "s/application\.json/application\.local\.json/g" .env.local
	@echo "\033[32mLocal configuration created, just edit your new \`secrets/application.local.json\` file for your needs\033[39m"
###< Create local configuration ###

###> Normalizes `composer.json` content ###
normalize-composer: ## Normalizes `composer.json` content
	@composer normalize
###< Normalizes `composer.json` content ###

###> Runs `phploc` and create json output ###
phploc: ## Runs `phploc` and create json output
	@php ./vendor/bin/phploc --log-json=./build/phploc.json ./src
###< Runs `phploc` and create json output ###
