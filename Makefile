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

ifneq ("$(wildcard /.dockerenv)", "")
	INSIDE_DOCKER_CONTAINER = 1
else
	INSIDE_DOCKER_CONTAINER = 0
endif

ifneq ("$(wildcard /.dockerenv)", "")
	INSIDE_CONTAINER_RUNTIME = 1
else
	INSIDE_CONTAINER_RUNTIME = 0
endif

# Global variables that we're using
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
CONSOLE := $(shell command -v bin/console)
OPENSSL_BIN := $(shell command -v openssl)
PHPDBG := $(shell command -v phpdbg)
COMPOSER_BIN := $(shell command -v composer)
endif
DOCKER := $(shell which docker)
CONTAINER_PREFIX := 'symfony-backend' # Check your `compose.yaml` file `container_name` property for this one
CONTAINER_COUNT := 5 # Check your `compose.yaml` file service count for this one
COMPOSE_PROD := docker compose -f compose.yaml -f compose.prod.yaml
COMPOSE_CI := docker compose -f compose.yaml -f docker/ci-compose.yaml
ifdef GITHUB_WORKFLOW
COMPOSE_EXEC := $(COMPOSE_CI)
else
COMPOSE_EXEC := docker compose
endif
export COLOR ?= auto
export NO_COLOR
CONTAINER_ENV := --env COLOR --env NO_COLOR $(foreach name,CI DATABASE_URL XDEBUG_MODE COVERALLS_REPO_TOKEN COVERALLS_RUN_LOCALLY GITHUB_TOKEN GITHUB_ACTIONS GITHUB_RUN_ID GITHUB_RUN_NUMBER GITHUB_JOB GITHUB_REF GITHUB_SHA GITHUB_HEAD_REF GITHUB_REPOSITORY GITHUB_EVENT_NAME GITHUB_BASE_REF GITHUB_SERVER_URL,$(if $(filter undefined,$(origin $(name))),,--env $(name)))

HOST_TARGETS := help info bash fish logs daemon daemon-build daemon-prod start-prod stop start start-build \
	docker-prune docker-kill-containers docker-remove-containers docker-remove-images \
	docker-image-check-updates docker-image-digest docker-image-verify-pins docker-builder-platforms \
	docker-setup-qemu docker-build-platform docker-build-multi-platform ci-start ci-stop
.DEFAULT_GOAL := help

ifeq ($(INSIDE_DOCKER_CONTAINER),0)
FORWARD_TARGETS := $(filter-out $(HOST_TARGETS),$(MAKECMDGOALS))
endif

ifneq ($(strip $(FORWARD_TARGETS)),)
.PHONY: $(MAKECMDGOALS)
$(FORWARD_TARGETS):
	@if [ "$$COLOR" = auto ]; then \
		if [ -t 2 ] && [ "$${TERM:-}" != dumb ]; then COLOR=always; else COLOR=never; fi; \
		export COLOR; \
	fi; \
	HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) $(COMPOSE_EXEC) exec -T $(CONTAINER_ENV) php make $@
ifneq ($(filter $(HOST_TARGETS),$(MAKECMDGOALS)),)
$(filter $(HOST_TARGETS),$(MAKECMDGOALS)):
	@$(MAKE) $@
endif
else

QUALITY_TARGETS := run-tests run-tests-php run-tests-phpdbg infection phpcs ecs ecs-fix \
	phpinsights phplint php-parallel-lint psalm psalm-shepherd psalm-github phpstan phpstan-github \
	lint-configuration lint-yaml lint-markdown composer-normalize composer-validate \
	composer-require-checker composer-unused
QUALITY_TARGETS += install install-tools update update-tools cache-clear cache-warmup \
	local-configuration configure-ci generate-jwt-keys generate-ssl-cert clear clear-tools phpmetrics
QUALITY_TARGETS += configuration project-stats application-info tool-versions check-licenses phploc \
	report-coveralls prepare-sonar-reports
QUALITY_GOALS := $(filter $(QUALITY_TARGETS),$(MAKECMDGOALS))
ifeq ($(QUALITY_OUTPUT_RUN),1)
QUALITY_GOALS :=
endif

ifneq ($(strip $(QUALITY_GOALS)),)
.PHONY: $(MAKECMDGOALS)
lint-markdown: QUALITY_LABEL := Markdown documentation
install: QUALITY_LABEL := Install application dependencies
install-tools: QUALITY_LABEL := Install tool dependencies
update: QUALITY_LABEL := Update application dependencies
update-tools: QUALITY_LABEL := Update tool dependencies
cache-clear: QUALITY_LABEL := Clear application cache
cache-warmup: QUALITY_LABEL := Rebuild application cache
local-configuration: QUALITY_LABEL := Create local configuration
configure-ci: QUALITY_LABEL := Configure CI environment and JWT keys
generate-jwt-keys: QUALITY_LABEL := Generate JWT key pair
generate-ssl-cert: QUALITY_LABEL := Generate local TLS certificates
clear: QUALITY_LABEL := Remove application and tool dependencies
clear-tools: QUALITY_LABEL := Remove tool dependencies
phpmetrics: QUALITY_LABEL := Generate PhpMetrics report
configuration: QUALITY_LABEL := Read application configuration
project-stats: QUALITY_LABEL := Read project statistics
application-info: QUALITY_LABEL := Read application information
tool-versions: QUALITY_LABEL := Read tool versions
check-licenses: QUALITY_LABEL := Read dependency license summary
phploc: QUALITY_LABEL := Generate PHPLOC report
report-coveralls: QUALITY_LABEL := Submit coverage to Coveralls
prepare-sonar-reports: QUALITY_LABEL := Prepare local Sonar reports; no upload is performed
ifeq ($(strip $(CONSOLE)),)
cache-warmup: export OUTPUT_SUCCESS_STATUS := SKIP
endif
$(QUALITY_GOALS):
ifneq ($(findstring n,$(filter-out --%,$(firstword $(MAKEFLAGS)))),)
	@$(MAKE) QUALITY_OUTPUT_RUN=1 $@
else
	+@bash scripts/command-output.sh "$(or $(QUALITY_LABEL),$@)" bash scripts/make.sh QUALITY_OUTPUT_RUN=1 $@
endif
$(filter-out $(QUALITY_TARGETS),$(MAKECMDGOALS)):
	@$(MAKE) $@
else

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
NOTICE_HOST = @printf "\033[33mRunning command from host machine by using 'docker compose exec' command\033[39m\n"
WARNING_DOCKER = @printf "\033[31mThis command must be run inside specified docker container and it's not\nrunning atm. Use \`make start\` and/or \`make daemon\` command to get\nnecessary container(s) running and after that run this command again.\033[39m\n"
HOST_CONTEXT_FAILURE = @bash -c 'source scripts/command-output.sh; output_status FAIL "Run this target on the Docker host, not inside a container"; exit 1'
CONTAINERS_UNAVAILABLE = @bash -c 'source scripts/command-output.sh; output_status FAIL "Required application containers are unavailable; inspect docker compose ps and logs"; exit 1'
CONTAINERS_ALREADY_RUNNING = @bash -c 'source scripts/command-output.sh; output_status FAIL "Application containers are already running; stop them before starting again"; exit 1'
ALL_DONE = @printf $(_TITLE) "All done" "Have a nice day"

_WARNING := "\033[33m[%s]\033[0m %s\n" # Yellow text for "printf"
_TITLE := "\033[32m[%s]\033[0m %s\n" # Green text for "printf"
_ERROR := "\033[31m[%s]\033[0m %s\n" # Red text for "printf"

# Run a command and decorate output for handled exit codes (1 and 2).
# Usage: $(call RUN_WITH_DECORATED_EXIT_OUTPUT,<command>,<label>)
define RUN_WITH_DECORATED_EXIT_OUTPUT
	@bash scripts/action-update-output.sh $(1)
endef

define MUTATION_WARNING
	@bash -c 'source scripts/command-output.sh; output_status WARN "$$1"' warning "$(1)"
endef

define STREAM_COMMAND
	@bash -c 'source scripts/command-output.sh; output_status RUN "$$1"; shift; exec "$$@"' stream "$(1)" $(2)
endef

define REPORT_PATHS
	@bash -c 'source scripts/command-output.sh; output_status INFO "$$1"' reports "$(1)"
endef

info_msg := Message
info:
	$(info_msg)

help:
	@bash scripts/make-help.sh

configuration: ## Prints out application current configuration
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo | jq -r . ${APPLICATION_CONFIG}
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make configuration
	$(ALL_DONE)
endif

cache-clear: ## Clears the cache
	$(call MUTATION_WARNING,Removing application cache; it will be regenerated as needed)
ifdef CONSOLE
	@bin/console cache:clear --no-warmup
else
	@rm -rf var/cache/*
endif

cache-warmup: cache-clear ## Warms up an empty cache
ifdef CONSOLE
	@bin/console cache:warmup
else
	@printf "Cache warmup skipped: bin/console is unavailable; cache cleanup completed.\n"
endif

generate-jwt-keys: ## Generates JWT auth keys
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(call MUTATION_WARNING,Replacing configured JWT keys; existing tokens may become invalid)
	@mkdir -p config/jwt
	@rm -f ${JWT_SECRET_KEY}
	@rm -f ${JWT_PUBLIC_KEY}
	@openssl genrsa -passout pass:${JWT_PASSPHRASE} -out ${JWT_SECRET_KEY} -aes256 4096
	@openssl rsa -passin pass:${JWT_PASSPHRASE} -pubout -in ${JWT_SECRET_KEY} -out ${JWT_PUBLIC_KEY}
	@chmod 644 ${JWT_SECRET_KEY}
	@chmod 644 ${JWT_PUBLIC_KEY}
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make generate-jwt-keys
	$(ALL_DONE)
endif

run-tests: ## Runs all tests via phpunit (Uses phpdbg if that is installed)
ifndef PHPDBG
	@${MAKE} run-tests-php
else
	@${MAKE} run-tests-phpdbg
endif

run-tests-php: ## Runs all tests via phpunit (pure PHP)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml
	$(call REPORT_PATHS,Test reports: build/logs/clover.xml and build/logs/junit.xml)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make run-tests-php
	$(ALL_DONE)
endif

run-tests-phpdbg: ## Runs all tests via phpunit (phpdbg)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test
	@phpdbg -qrr ./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml
	$(call REPORT_PATHS,Test reports: build/logs/clover.xml and build/logs/junit.xml)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make run-tests-phpdbg
	$(ALL_DONE)
endif

infection: ## Runs Infection to codebase
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build/infection
	@bin/console cache:clear --env=test
	@./vendor/bin/infection --threads=8 --only-covered --show-mutations --test-framework-options="--testsuite=Functional,Integration,Unit"
	$(call REPORT_PATHS,Mutation reports: build/infection (see native output for generated files))
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make infection
	$(ALL_DONE)
endif

phpmetrics: ## Generates PhpMetrics static analysis
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build/phpmetrics
	$(call MUTATION_WARNING,Writing the HTML report to build/phpmetrics; existing report files may be replaced)
	@if [ ! -f build/logs/junit.xml ] ; then \
		bash -c 'source scripts/command-output.sh; output_status WARN "build/logs/junit.xml not found; running application tests to generate it"' ; \
		$(MAKE) -s run-tests || exit $$?; \
	fi;
	@test -f build/logs/junit.xml || { printf 'ERROR: Required report build/logs/junit.xml was not generated.\n' >&2; exit 1; }
	@php ./vendor/bin/phpmetrics --version
	@./vendor/bin/phpmetrics --junit=build/logs/junit.xml --report-html=build/phpmetrics .
	$(call REPORT_PATHS,HTML report: build/phpmetrics)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make phpmetrics
endif

phpcs: ## Runs PHP CodeSniffer
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phpcs --version
	@php ./vendor/bin/phpcs --standard=PSR2 --colors -p src tests
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make phpcs
	$(ALL_DONE)
endif

ecs: ## Runs The Easiest Way to Use Any Coding Standard
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs --clear-cache src tests
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make ecs
	$(ALL_DONE)
endif

ecs-fix: ## Runs The Easiest Way to Use Any Coding Standard to fix issues
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(call MUTATION_WARNING,Applying coding-standard fixes to files under src and tests)
	@php ./vendor/bin/ecs --version
	@php ./vendor/bin/ecs src tests --clear-cache --fix
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make ecs-fix
	$(ALL_DONE)
endif

phpinsights: ## Runs PHP Insights
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php -d error_reporting=0 ./vendor/bin/phpinsights analyse --no-interaction --min-quality=95 --min-complexity=85 --min-architecture=100 --min-style=100
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make phpinsights
	$(ALL_DONE)
endif

phplint: ## Runs PHPLint
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@php ./vendor/bin/phplint --no-cache
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make phplint
	$(ALL_DONE)
endif

php-parallel-lint: ## Lint PHP files with `php-parallel-lint`
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@./vendor/bin/parallel-lint --exclude .git --exclude app --exclude tools --exclude var --exclude vendor --colors --show-deprecated --blame .
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make php-parallel-lint
	$(ALL_DONE)
endif

psalm: ## Runs Psalm static analysis tool
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --report=./build/psalm.json
	$(call REPORT_PATHS,JSON report: build/psalm.json)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make psalm
	$(ALL_DONE)
endif

psalm-shepherd: ## Runs Psalm static analysis tool + report results to shepherd
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --show-info=true --report=./build/psalm.json
	$(call REPORT_PATHS,Local JSON report: build/psalm.json; Shepherd submission status is reported by Psalm)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make psalm-shepherd
	$(ALL_DONE)
endif

psalm-github: ## Runs Psalm static analysis tool (GitHub)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build
	@@bin/console cache:clear
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --show-info=true --report=./build/psalm.json --output-format=github
	$(call REPORT_PATHS,Local JSON report: build/psalm.json; Shepherd submission status is reported by Psalm)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make psalm-github
	$(ALL_DONE)
endif

phpstan: ## Runs PHPStan static analysis tool
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@@bin/console cache:clear
	@bin/console cache:clear
	@sleep 3
	@./tools/02_phpstan/vendor/phpstan/phpstan/phpstan --version
	@./tools/02_phpstan/vendor/phpstan/phpstan/phpstan -v
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make phpstan
	$(ALL_DONE)
endif

phpstan-github: ## Runs PHPStan static analysis tool (GitHub)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@@bin/console cache:clear
	@bin/console cache:clear
	@sleep 3
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan -v --error-format=github
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make phpstan
	$(ALL_DONE)
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
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make lint-configuration
	$(ALL_DONE)
endif

lint-yaml: ## Lint config YAML files
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@@bin/console lint:yaml config --parse-tags
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make lint-yaml
	$(ALL_DONE)
endif

check-skills: ## Validate skill metadata, index coverage, and repository Markdown links inside Docker
	@bash scripts/command-output.sh "Skill metadata and documentation links" python3 scripts/check-skills.py

lint-workflows: ## Validate GitHub workflows, embedded shell, and local action metadata
	@bash scripts/command-output.sh "GitHub workflows and local action metadata" bash scripts/lint-workflows.sh

lint-markdown: ## Lint Markdown files
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash scripts/markdown-output.sh bash -lc 'if command -v npx >/dev/null 2>&1; then npx --yes markdownlint-cli2 "**/*.md" "!vendor/**" "!var/**" "!node_modules/**" "!tools/**/vendor/**" "!.git/**"; elif [ -f "$${HOME}/.nvm/nvm.sh" ]; then . "$${HOME}/.nvm/nvm.sh" && nvm use --silent default >/dev/null && npx --yes markdownlint-cli2 "**/*.md" "!vendor/**" "!var/**" "!node_modules/**" "!tools/**/vendor/**" "!.git/**"; else echo "npx is not available. Install Node.js/npm (or nvm with a default Node version)."; exit 127; fi'
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make lint-markdown
	$(ALL_DONE)
endif

clear: ## Clean vendor and tool dependencies
	$(call MUTATION_WARNING,Deleting vendor and tool vendor directories; dependencies must be reinstalled)
	@rm -rf vendor
	@${MAKE} clear-tools

clear-tools: ## Clears all tools dependencies
	$(call MUTATION_WARNING,Deleting vendor directories under tools; tool dependencies must be reinstalled)
	@find tools -type d -name vendor -prune -exec rm -rf -- {} +
	@printf 'Restart development containers after reinstalling dependencies.\n'

check-dependencies-latest: ## Checks if any vendor dependency can be updated (latest versions)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash scripts/command-output.sh "Vendor dependency update check (latest)" bin/console check-dependencies
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make check-dependencies-latest
	$(ALL_DONE)
endif

check-dependencies-minor: ## Checks if any vendor dependency can be updated (only minor versions)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash scripts/command-output.sh "Vendor dependency update check (minor)" bin/console check-dependencies --level=minor
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make check-dependencies-minor
	$(ALL_DONE)
endif

check-dependencies-patch: ## Checks if any vendor dependency can be updated (only patch versions)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash scripts/command-output.sh "Vendor dependency update check (patch)" bin/console check-dependencies --level=patch
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make check-dependencies-patch
	$(ALL_DONE)
endif

check-licenses: ## Check vendor licenses
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash -o pipefail -c 'composer license --format=json | jq -r '"'"'.dependencies[] | .license | join(" OR ") | if length == 0 then "UNSPECIFIED" else . end'"'"' | sort | uniq -c | sort -rn'
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make check-licenses
	$(ALL_DONE)
endif

update: ## Update composer dependencies
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(call MUTATION_WARNING,Updating application dependencies and lockfiles; Composer hooks may update tools and application cache)
	@php -d memory_limit=-1 /usr/bin/composer update --with-all-dependencies --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make update
	$(ALL_DONE)
endif

update-tools: ## Update composer tools dependencies
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(call MUTATION_WARNING,Updating dependency versions and lockfiles in tool groups)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all update --no-progress --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make update-tools
	$(ALL_DONE)
endif

install-tools: ## Install composer tools dependencies
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(call MUTATION_WARNING,Installing tool dependencies and rebuilding their autoloaders)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all install --no-progress --optimize-autoloader
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make install-tools
	$(ALL_DONE)
endif

bash: ## Get bash inside PHP container
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(CONTAINERS_UNAVAILABLE)
else
	$(call STREAM_COMMAND,Opening Bash in the PHP container,env HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php bash --rcfile /app/scripts/bashrc.sh -i)
endif

fish: ## Get fish inside PHP container
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(CONTAINERS_UNAVAILABLE)
else
	$(call STREAM_COMMAND,Opening Fish in the PHP container,env HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php fish)
endif

logs: ## Show logs from all containers
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(CONTAINERS_UNAVAILABLE)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(CONTAINERS_UNAVAILABLE)
else
	$(call STREAM_COMMAND,Following PHP nginx and MariaDB logs; Ctrl-C stops following,env HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose logs --follow php nginx mariadb)
endif

daemon: ## Start application in development mode in background
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(CONTAINERS_ALREADY_RUNNING)
else
	$(call MUTATION_WARNING,Starting development services; entrypoints may install dependencies generate keys and run migrations)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="Development containers started; readiness was not verified" bash scripts/command-output.sh "Start development services in background" docker compose up --detach
endif

daemon-build: ## Build containers and start application in development mode in background
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(CONTAINERS_ALREADY_RUNNING)
else
	$(call MUTATION_WARNING,Building and starting development services; entrypoints may install dependencies generate keys and run migrations)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="Development containers built and started; readiness was not verified" bash scripts/command-output.sh "Build and start development services in background" docker compose up --build --detach
endif

daemon-prod: ## Build and start application in local production mode in background
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(CONTAINERS_ALREADY_RUNNING)
else
	$(call MUTATION_WARNING,Building and starting local production; initialization may generate keys and run migrations; waiting for Compose health checks)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="Local production services passed Compose running/health checks; HTTP was not verified" bash scripts/command-output.sh "Build and start local production; wait for Compose health checks" $(COMPOSE_PROD) up --build --detach --wait
endif

start-prod: ## Build and start application in local production mode + watch output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(CONTAINERS_ALREADY_RUNNING)
else
	$(call MUTATION_WARNING,Building and attaching to local production; initialization may generate keys and run migrations; Ctrl-C requests shutdown)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="Local production foreground session ended" bash scripts/command-output.sh "Build and attach to local production services" $(COMPOSE_PROD) up --build
endif

stop: ## Stop application containers (development or local production)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call MUTATION_WARNING,Stopping and removing application containers and networks; named volumes are retained)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="Application containers stopped and removed; named volumes retained" bash scripts/command-output.sh "Stop application containers" docker compose down
endif

start: ## Start application in development mode + watch output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(CONTAINERS_ALREADY_RUNNING)
else
	$(call MUTATION_WARNING,Starting and attaching to development services; entrypoints may install dependencies generate keys and run migrations; Ctrl-C requests shutdown)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="Development foreground session ended; readiness was not verified" bash scripts/command-output.sh "Start and attach to development services" docker compose up
endif

start-build: ## Build containers and start application in development mode + watch output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else ifneq ($(RUNNING_SOME_CONTAINERS), 0)
	$(CONTAINERS_ALREADY_RUNNING)
else
	$(call MUTATION_WARNING,Building and attaching to development services; entrypoints may install dependencies generate keys and run migrations; Ctrl-C requests shutdown)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="Development build and foreground session ended; readiness was not verified" bash scripts/command-output.sh "Build and attach to development services" docker compose up --build
endif

local-configuration: ## Create local configuration files
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(call MUTATION_WARNING,Overwriting .env.local and secrets/application.local.json with template configuration)
	@cp /app/.env /app/.env.local
	@cp /app/secrets/application.json /app/secrets/application.local.json
	@sed -i "s/application\.json/application\.local\.json/g" .env.local
	@printf 'Edit secrets/application.local.json for your local requirements.\n'
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make local-configuration
	$(ALL_DONE)
endif

composer-normalize: ## Normalizes `composer.json` file content
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(call MUTATION_WARNING,Rewriting composer.json into normalized formatting)
	@composer normalize
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make composer-normalize
	$(ALL_DONE)
endif

composer-validate: ## Validate `composer.json` file content
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@composer validate --no-check-version
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make composer-validate
	$(ALL_DONE)
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
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make composer-require-checker
	$(ALL_DONE)
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
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make composer-unused
	$(ALL_DONE)
endif

phploc: ## Runs `phploc` and create json output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@mkdir -p build
	@php ./vendor/bin/phploc --log-json=./build/phploc.json ./src
	$(call REPORT_PATHS,JSON report: build/phploc.json)
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make phploc
	$(ALL_DONE)
endif

check-security: ## Checks that application doesn't have installed dependencies with known security vulnerabilities
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash scripts/check-security.sh
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make check-security
	$(ALL_DONE)
endif

docker-prune: ## Kill all running containers and prune all docker stuff
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@bash scripts/docker-maintenance.sh prune
endif

docker-kill-containers: ## Kill all running docker containers
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) bash scripts/docker-maintenance.sh kill
endif

docker-remove-containers: ## Remove all docker containers
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) bash scripts/docker-maintenance.sh containers
endif

docker-remove-images: ## Remove all docker images
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) bash scripts/docker-maintenance.sh images
endif

# Multi-platform Docker image helpers, see `doc/DOCKER_MULTI_PLATFORM.md`.
# Missing values are asked interactively, or those can be given as variables.
# These need host Docker, including on CI runners.
docker-image-check-updates: ## Report newer stable Docker Hub image versions
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@IMAGE="$(IMAGE)" ./scripts/docker-images.sh check-updates
endif

docker-image-digest: ## Show multi-platform index digest for image (asks IMAGE)
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@IMAGE="$(IMAGE)" ./scripts/docker-images.sh digest
endif

docker-image-verify-pins: ## Verify base images are multi-platform
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@bash scripts/command-output.sh "Repository Dockerfile pins (live registry verification)" ./scripts/docker-images.sh verify
endif

docker-builder-platforms: ## Show platforms supported by current buildx builder
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@./scripts/docker-images.sh platforms
endif

docker-setup-qemu: ## Install QEMU emulators for cross-platform builds (asks PLATFORMS)
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@PLATFORMS="$(PLATFORMS)" ./scripts/docker-images.sh setup-qemu
endif

docker-build-platform: ## Build image for single platform (asks PLATFORM, DOCKERFILE, TARGET, TAG)
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@PLATFORM="$(PLATFORM)" DOCKERFILE="$(DOCKERFILE)" TARGET="$(TARGET)" TAG="$(TAG)" ./scripts/docker-images.sh build
endif

docker-build-multi-platform: ## Build and push multi-platform image (asks PLATFORMS, DOCKERFILE, TARGET, TAG, BUILDER)
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	@PLATFORMS="$(PLATFORMS)" DOCKERFILE="$(DOCKERFILE)" TARGET="$(TARGET)" TAG="$(TAG)" BUILDER="$(BUILDER)" ./scripts/docker-images.sh push
endif

generate-ssl-cert: ## Generate self signed SSL certificates inside the PHP container
	$(call MUTATION_WARNING,Replacing local CA and TLS keys and certificates in docker/nginx/ssl; OpenSSL may prompt for input)
	@cd docker/nginx/ssl && ./create-keys.sh

check-action-updates: ## Check pinned GitHub Actions and available updates
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(call RUN_WITH_DECORATED_EXIT_OUTPUT,./scripts/check-action-updates.sh,check-action-updates)
else ifeq ($(strip $(RUNNING_SOME_CONTAINERS)),)
	$(WARNING_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make check-action-updates
endif


project-stats: ## Create simple project stats
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@./scripts/project-stats.sh
else ifeq ($(RUNNING_SOME_CONTAINERS), 0)
	$(WARNING_DOCKER)
else ifneq ($(RUNNING_ALL_CONTAINERS), 1)
	$(ERROR_DOCKER)
else
	$(NOTICE_HOST)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose exec php make project-stats
	$(ALL_DONE)
endif

ci-start: ## Build and start the Compose PHP development service for CI (host only)
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call MUTATION_WARNING,Building and starting only the CI PHP service; application initialization is disabled)
	@mkdir -p "$${COMPOSER_CACHE_DIR:-/tmp/symfony-backend-composer-cache}"
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="CI PHP service started; application initialization and readiness were not verified" bash scripts/command-output.sh "Build and start the CI PHP service" $(COMPOSE_CI) up --build --detach --no-deps php
endif

ci-stop: ## Stop CI containers (host only)
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call MUTATION_WARNING,Stopping and removing CI containers and networks; named volumes are retained)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="CI containers stopped and removed; named volumes retained" bash scripts/command-output.sh "Stop CI containers" $(COMPOSE_CI) down
endif

install: ## Install project Composer dependencies
	$(call MUTATION_WARNING,Installing application dependencies and rebuilding autoloaders; Composer hooks may modify cache)
	@composer install --no-progress --optimize-autoloader

application-info: ## Display Symfony application information
	@bin/console about

check-requirements: ## Check application runtime requirements
	@bash scripts/command-output.sh "Application runtime requirements" php ./vendor/bin/requirements-checker

tool-versions: ## Display PHP, Composer, Symfony, and PHPUnit versions
	@php --version
	@bin/console --version
	@php ./vendor/bin/phpunit --version
	@composer --version

configure-ci: ## Configure the application and JWT keys for CI only
	@test "$$GITHUB_ACTIONS" = true || { echo "configure-ci is restricted to GitHub Actions." >&2; exit 1; }
	$(call MUTATION_WARNING,Overwriting .env and .env.test from CI templates and replacing configured JWT keys)
	@cp .env.gh-actions .env
	@cp .env.gh-actions .env.test
	@$(MAKE) generate-jwt-keys

report-coveralls: ## Upload coverage to Coveralls
	@php ./vendor/bin/php-coveralls -v

prepare-sonar-reports: ## Copy test reports with relative paths for the host-side Sonar action
	@bash scripts/prepare-sonar-reports.sh

test-make-routing: ## Test host and CI container command routing
	@bash scripts/command-output.sh "Make container routing (offline mocked tests)" bash scripts/tests/make-container-test.sh

test-docker-images: ## Run offline mocked helper tests; use docker-image-verify-pins to check actual Dockerfile pins
	@bash scripts/tests/docker-images-test.sh
endif
endif
