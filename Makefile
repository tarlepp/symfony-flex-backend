ifndef APP_ENV
	include .env
endif

.DEFAULT_GOAL := help
.PHONY: help
help:
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-17s[0m %s\n", $$1, $$2}'

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
	mkdir -p config/jwt
	openssl genrsa -passout pass:${JWT_PASSPHRASE} -out ${JWT_PRIVATE_KEY_PATH} -aes256 4096
	openssl rsa -passin pass:${JWT_PASSPHRASE} -pubout -in ${JWT_PRIVATE_KEY_PATH} -out ${JWT_PUBLIC_KEY_PATH}
	@echo "\033[32mRSA key pair successfully generated\033[39m"
###< lexik/jwt-authentication-bundle ###

###> phpunit ###
run-tests: ## Runs all tests via phpunit
	@echo "\033[32mRunning PhpUnit\033[39m"
	@php ./vendor/bin/phpunit --version
	@mkdir -p build/logs
	@bin/console cache:clear --env=test
	@./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml

run-tests-fastest: ## Runs all test via fastest
	@mkdir -p build/fastest
	@bin/console cache:clear --env=test
	@find tests/ -name "*Test.php" | php ./vendor/bin/fastest -v -p 8 -b "php ./tests/bootstrap.php" "php ./vendor/bin/phpunit {} -c phpunit.fastest.xml --coverage-php build/fastest/{n}.cov --log-junit build/fastest/{n}.xml";

merge-clover: ## Creates clover from fastest run
	@./vendor/bin/phpcov merge ./build/fastest/ --clover=./build/logs/clover.xml

merge-junit: ## Creates JUnit xml from fastest run
	@php merge-phpunit-xml.php ./build/fastest/ ./build/logs/junit.xml
###< phpunit ###

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
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs --clear-cache check src
###< ecs ###

###> psalm ###
psalm: ## Runs Psalm static analysis tool
	@echo "\033[32mRunning Psalm - A static analysis tool for PHP\033[39m"
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache
###< psalm ###

###> phpstan ###
phpstan: ## Runs PHPStan static analysis tool
	@echo "\033[32mRunning PHPStan - PHP Static Analysis Tool\033[39m"
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan analyze --level 7 src
###< phpstan ###
