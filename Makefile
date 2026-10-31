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
COMPOSE_PROD := docker compose -f compose.yaml -f compose.prod.yaml
COMPOSE_CI := docker compose -f compose.yaml -f docker/ci-compose.yaml
ifdef GITHUB_WORKFLOW
COMPOSE_EXEC := $(COMPOSE_CI)
else
COMPOSE_EXEC := docker compose
endif
export COLOR ?= auto
export NO_COLOR
NATIVE_COLOR := $(shell bash -c 'source scripts/command-output.sh; if [ -n "$$OUTPUT_RESET" ]; then printf always; else printf never; fi')
export COMPOSE_ANSI := $(NATIVE_COLOR)
CONTAINER_ENV := --env COLOR --env NO_COLOR $(foreach name,CI DATABASE_URL XDEBUG_MODE COVERALLS_REPO_TOKEN COVERALLS_RUN_LOCALLY GITHUB_TOKEN GITHUB_ACTIONS GITHUB_RUN_ID GITHUB_RUN_NUMBER GITHUB_JOB GITHUB_REF GITHUB_SHA GITHUB_HEAD_REF GITHUB_REPOSITORY GITHUB_EVENT_NAME GITHUB_BASE_REF GITHUB_SERVER_URL,$(if $(filter undefined,$(origin $(name))),,--env $(name)))

.DEFAULT_GOAL := help

# Target metadata: name, help group, execution (host/wrapped/direct), risk, label.
define REGISTER_TARGET
$(if $(filter $(1),$(COMMAND_TARGETS)),$(error Duplicate target metadata: $(1)))
$(if $(filter host wrapped direct,$(3)),,$(error Invalid execution metadata: $(1)))
$(eval COMMAND_TARGETS += $(1))
$(if $(filter host,$(3)),$(eval HOST_TARGETS += $(1)))
$(if $(filter wrapped,$(3)),$(eval WRAPPED_TARGETS += $(1)))
$(eval $(1): COMMAND_LABEL := $(or $(5),$(1)))
endef

$(call REGISTER_TARGET,help,hidden,host)
$(call REGISTER_TARGET,info,hidden,host)
$(call REGISTER_TARGET,bash,lifecycle,host)
$(call REGISTER_TARGET,fish,lifecycle,host)
$(call REGISTER_TARGET,logs,lifecycle,host)
$(call REGISTER_TARGET,start,lifecycle,host)
$(call REGISTER_TARGET,start-build,lifecycle,host)
$(call REGISTER_TARGET,start-prod,lifecycle,host)
$(call REGISTER_TARGET,daemon,lifecycle,host)
$(call REGISTER_TARGET,daemon-build,lifecycle,host)
$(call REGISTER_TARGET,daemon-prod,lifecycle,host)
$(call REGISTER_TARGET,stop,lifecycle,host,DESTRUCTIVE)
$(call REGISTER_TARGET,ci-start,lifecycle,host)
$(call REGISTER_TARGET,ci-stop,lifecycle,host,DESTRUCTIVE)
$(call REGISTER_TARGET,docker-prune,docker,host,DESTRUCTIVE)
$(call REGISTER_TARGET,docker-kill-containers,docker,host,DESTRUCTIVE)
$(call REGISTER_TARGET,docker-remove-containers,docker,host,DESTRUCTIVE)
$(call REGISTER_TARGET,docker-remove-images,docker,host,DESTRUCTIVE)
$(call REGISTER_TARGET,docker-image-check-updates,docker,host)
$(call REGISTER_TARGET,docker-image-digest,docker,host)
$(call REGISTER_TARGET,docker-image-verify-pins,docker,host)
$(call REGISTER_TARGET,docker-builder-platforms,docker,host)
$(call REGISTER_TARGET,docker-setup-qemu,docker,host)
$(call REGISTER_TARGET,docker-build-platform,docker,host,OVERWRITE)
$(call REGISTER_TARGET,docker-build-multi-platform,docker,host,OVERWRITE)
$(call REGISTER_TARGET,install,setup,wrapped,,Install application dependencies)
$(call REGISTER_TARGET,install-tools,setup,wrapped,,Install tool dependencies)
$(call REGISTER_TARGET,update,setup,wrapped,,Update application dependencies)
$(call REGISTER_TARGET,update-tools,setup,wrapped,,Update tool dependencies)
$(call REGISTER_TARGET,cache-clear,setup,wrapped,DESTRUCTIVE,Clear application cache)
$(call REGISTER_TARGET,cache-warmup,setup,wrapped,DESTRUCTIVE,Rebuild application cache)
$(call REGISTER_TARGET,local-configuration,setup,wrapped,OVERWRITE,Create local configuration)
$(call REGISTER_TARGET,configure-ci,setup,wrapped,OVERWRITE,Configure CI environment and JWT keys)
$(call REGISTER_TARGET,generate-jwt-keys,setup,wrapped,OVERWRITE,Generate JWT key pair)
$(call REGISTER_TARGET,generate-ssl-cert,setup,wrapped,OVERWRITE,Generate local TLS certificates)
$(call REGISTER_TARGET,clear,setup,wrapped,DESTRUCTIVE,Remove application and tool dependencies)
$(call REGISTER_TARGET,clear-tools,setup,wrapped,DESTRUCTIVE,Remove tool dependencies)
$(call REGISTER_TARGET,run-tests,tests,wrapped)
$(call REGISTER_TARGET,run-tests-php,tests,wrapped)
$(call REGISTER_TARGET,run-tests-phpdbg,tests,wrapped)
$(call REGISTER_TARGET,infection,tests,wrapped)
$(call REGISTER_TARGET,test-make-routing,tests,direct)
$(call REGISTER_TARGET,test-command-output,tests,direct)
$(call REGISTER_TARGET,test-docker-images,tests,direct)
$(call REGISTER_TARGET,check-dependencies-latest,checks,direct)
$(call REGISTER_TARGET,check-dependencies-minor,checks,direct)
$(call REGISTER_TARGET,check-dependencies-patch,checks,direct)
$(call REGISTER_TARGET,check-action-updates,checks,direct)
$(call REGISTER_TARGET,check-security,checks,direct)
$(call REGISTER_TARGET,check-licenses,checks,wrapped,,Read dependency license summary)
$(call REGISTER_TARGET,phpcs,quality,wrapped)
$(call REGISTER_TARGET,ecs,quality,wrapped)
$(call REGISTER_TARGET,ecs-fix,quality,wrapped,OVERWRITE)
$(call REGISTER_TARGET,phpinsights,quality,wrapped)
$(call REGISTER_TARGET,phplint,quality,wrapped)
$(call REGISTER_TARGET,php-parallel-lint,quality,wrapped)
$(call REGISTER_TARGET,psalm,quality,wrapped)
$(call REGISTER_TARGET,psalm-shepherd,quality,wrapped)
$(call REGISTER_TARGET,psalm-github,quality,wrapped)
$(call REGISTER_TARGET,phpstan,quality,wrapped)
$(call REGISTER_TARGET,phpstan-github,quality,wrapped)
$(call REGISTER_TARGET,lint-configuration,quality,wrapped)
$(call REGISTER_TARGET,lint-yaml,quality,wrapped)
$(call REGISTER_TARGET,lint-markdown,quality,wrapped,,Markdown documentation)
$(call REGISTER_TARGET,composer-normalize,quality,wrapped,OVERWRITE)
$(call REGISTER_TARGET,composer-validate,quality,wrapped)
$(call REGISTER_TARGET,composer-require-checker,quality,wrapped)
$(call REGISTER_TARGET,composer-unused,quality,wrapped)
$(call REGISTER_TARGET,check-skills,quality,direct)
$(call REGISTER_TARGET,lint-workflows,quality,direct)
$(call REGISTER_TARGET,check-requirements,quality,direct)
$(call REGISTER_TARGET,configuration,information,wrapped,,Read application configuration)
$(call REGISTER_TARGET,project-stats,information,wrapped,,Read project statistics)
$(call REGISTER_TARGET,application-info,information,wrapped,,Read application information)
$(call REGISTER_TARGET,tool-versions,information,wrapped,,Read tool versions)
$(call REGISTER_TARGET,phpmetrics,reports,wrapped,,Generate PhpMetrics report)
$(call REGISTER_TARGET,phploc,reports,wrapped,,Generate PHPLOC report)
$(call REGISTER_TARGET,report-coveralls,reports,wrapped,,Submit coverage to Coveralls)
$(call REGISTER_TARGET,prepare-sonar-reports,reports,wrapped,OVERWRITE,Prepare local Sonar reports; no upload is performed)

psalm-shepherd psalm-github: export OUTPUT_SUCCESS_MESSAGE := Psalm analysis completed; local report: build/psalm.json; Shepherd upload success is not verified
report-coveralls: export OUTPUT_SUCCESS_MESSAGE := Coveralls client completed; consult native HTTP status for upload confirmation

.PHONY: $(COMMAND_TARGETS)

ifeq ($(INSIDE_DOCKER_CONTAINER),0)
FORWARD_TARGETS := $(filter-out $(HOST_TARGETS),$(MAKECMDGOALS))
endif

ifneq ($(strip $(FORWARD_TARGETS)),)
.PHONY: $(MAKECMDGOALS)
$(FORWARD_TARGETS):
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) bash scripts/compose-context.sh php $(COMPOSE_EXEC)
	@if [ "$$COLOR" = auto ]; then \
		if [ -t 2 ] && [ "$${TERM:-}" != dumb ]; then COLOR=always; else COLOR=never; fi; \
		export COLOR; \
	fi; \
	HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) $(COMPOSE_EXEC) exec -T $(CONTAINER_ENV) php bash scripts/make.sh $@
ifneq ($(filter $(HOST_TARGETS),$(MAKECMDGOALS)),)
$(filter $(HOST_TARGETS),$(MAKECMDGOALS)):
	@$(MAKE) $@
endif
else

WRAPPED_GOALS := $(filter $(WRAPPED_TARGETS),$(MAKECMDGOALS))
ifeq ($(COMMAND_OUTPUT_RUN),1)
WRAPPED_GOALS :=
endif

ifneq ($(strip $(WRAPPED_GOALS)),)
ifeq ($(strip $(CONSOLE)),)
cache-warmup: export OUTPUT_SUCCESS_STATUS := SKIP
endif
$(WRAPPED_GOALS):
ifneq ($(findstring n,$(filter-out --%,$(firstword $(MAKEFLAGS)))),)
	@$(MAKE) COMMAND_OUTPUT_RUN=1 $@
else
	+@bash scripts/command-output.sh --run "$(COMMAND_LABEL)" bash scripts/make.sh COMMAND_OUTPUT_RUN=1 $@
endif
$(filter-out $(WRAPPED_TARGETS),$(MAKECMDGOALS)):
	@$(MAKE) $@
else

ANSI_FLAG := $(if $(filter always,$(NATIVE_COLOR)),--ansi,--no-ansi)
COLORS_FLAG := $(if $(filter always,$(NATIVE_COLOR)),--colors,--no-colors)
PSALM_COLOR_FLAG := $(if $(filter never,$(NATIVE_COLOR)),--monochrome)

HOST_CONTEXT_FAILURE = @bash scripts/command-output.sh --status FAIL "Run this target on the Docker host, not inside a container"; exit 1

define MUTATION_WARNING
	@bash scripts/command-output.sh --status WARN "$(1)"
endef

define STREAM_COMMAND
	@bash scripts/command-output.sh --stream "$(1)" $(2)
endef

define REPORT_PATHS
	@bash scripts/report-output.sh "$(1)" $(2)
endef

define COMPOSE_CONTEXT
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) bash scripts/compose-context.sh $(1) $(2)
endef

define COMPOSE_RUN
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) OUTPUT_SUCCESS_MESSAGE="$(3)" bash scripts/command-output.sh --run "$(1)" $(2)
endef

info_msg := Message
info:
	@bash scripts/command-output.sh --run "Command information" bash scripts/command-output.sh --status INFO '$(subst ','"'"',$(info_msg))'

help:
	@bash scripts/command-output.sh --run "Command help" bash scripts/make-help.sh "$(GROUP)"

configuration: ## Prints out application current configuration
	@echo | jq -r . ${APPLICATION_CONFIG}

cache-clear: ## Clears the cache
	$(call MUTATION_WARNING,Removing application cache; it will be regenerated as needed)
ifdef CONSOLE
	@bin/console cache:clear --no-warmup $(ANSI_FLAG)
else
	@rm -rf var/cache/*
endif

cache-warmup: cache-clear ## Warms up an empty cache
ifdef CONSOLE
	@bin/console cache:warmup $(ANSI_FLAG)
else
	@bash scripts/command-output.sh --status SKIP "Cache warmup skipped: bin/console is unavailable; cache cleanup completed"
endif

generate-jwt-keys: ## Generates JWT auth keys
	$(call MUTATION_WARNING,Replacing configured JWT keys; existing tokens may become invalid)
	@mkdir -p config/jwt
	@rm -f ${JWT_SECRET_KEY}
	@rm -f ${JWT_PUBLIC_KEY}
	@openssl genrsa -passout pass:${JWT_PASSPHRASE} -out ${JWT_SECRET_KEY} -aes256 4096
	@openssl rsa -passin pass:${JWT_PASSPHRASE} -pubout -in ${JWT_SECRET_KEY} -out ${JWT_PUBLIC_KEY}
	@chmod 644 ${JWT_SECRET_KEY}
	@chmod 644 ${JWT_PUBLIC_KEY}

run-tests: ## Runs all tests via phpunit (Uses phpdbg if that is installed)
ifndef PHPDBG
	@${MAKE} run-tests-php
else
	@${MAKE} run-tests-phpdbg
endif

run-tests-php: ## Runs all tests via phpunit (pure PHP)
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test $(ANSI_FLAG)
	@./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml --colors=$(NATIVE_COLOR)
	$(call REPORT_PATHS,Test reports: build/logs/clover.xml and build/logs/junit.xml,build/logs/clover.xml build/logs/junit.xml)

run-tests-phpdbg: ## Runs all tests via phpunit (phpdbg)
	@php ./vendor/bin/phpunit --version
	@rm -rf build/logs
	@mkdir -p build/logs
	@rm -rf ./var/cache/test*
	@bin/console cache:warmup --env=test $(ANSI_FLAG)
	@phpdbg -qrr ./vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml --colors=$(NATIVE_COLOR)
	$(call REPORT_PATHS,Test reports: build/logs/clover.xml and build/logs/junit.xml,build/logs/clover.xml build/logs/junit.xml)

infection: ## Runs Infection to codebase
	@mkdir -p build/infection
	@bin/console cache:clear --env=test $(ANSI_FLAG)
	@./vendor/bin/infection $(ANSI_FLAG) --threads=8 --only-covered --show-mutations --test-framework-options="--testsuite=Functional,Integration,Unit --colors=$(NATIVE_COLOR)"
	@bash scripts/command-output.sh --status INFO "Mutation report directory: build/infection; see native output for generated files"

phpmetrics: ## Generates PhpMetrics static analysis
	@mkdir -p build/phpmetrics
	$(call MUTATION_WARNING,Writing the HTML report to build/phpmetrics; existing report files may be replaced)
	@if [ ! -f build/logs/junit.xml ] ; then \
		bash scripts/command-output.sh --status WARN "build/logs/junit.xml not found; running application tests to generate it" ; \
		$(MAKE) -s run-tests || exit $$?; \
	fi;
	@test -f build/logs/junit.xml || { bash scripts/command-output.sh --status FAIL "Required report build/logs/junit.xml was not generated"; exit 1; }
	@php ./vendor/bin/phpmetrics --version
	@bash scripts/native-output.sh ./vendor/bin/phpmetrics --junit=build/logs/junit.xml --report-html=build/phpmetrics .
	$(call REPORT_PATHS,HTML report: build/phpmetrics,build/phpmetrics/index.html)

phpcs: ## Runs PHP CodeSniffer
	@php ./vendor/bin/phpcs --version
	@php ./vendor/bin/phpcs --standard=PSR2 $(COLORS_FLAG) -p src tests

ecs: ## Runs The Easiest Way to Use Any Coding Standard
	@php ./vendor/bin/ecs --version
	@bash scripts/native-output.sh php ./vendor/bin/ecs $(ANSI_FLAG) --clear-cache src tests

ecs-fix: ## Runs The Easiest Way to Use Any Coding Standard to fix issues
	$(call MUTATION_WARNING,Applying coding-standard fixes to files under src and tests)
	@php ./vendor/bin/ecs --version
	@bash scripts/native-output.sh php ./vendor/bin/ecs $(ANSI_FLAG) src tests --clear-cache --fix

phpinsights: ## Runs PHP Insights
	@php -d error_reporting=0 ./vendor/bin/phpinsights analyse $(ANSI_FLAG) --no-interaction --min-quality=95 --min-complexity=85 --min-architecture=100 --min-style=100

phplint: ## Runs PHPLint
	@php ./vendor/bin/phplint --no-cache $(ANSI_FLAG)

php-parallel-lint: ## Lint PHP files with `php-parallel-lint`
	@./vendor/bin/parallel-lint --exclude .git --exclude app --exclude tools --exclude var --exclude vendor $(COLORS_FLAG) --show-deprecated --blame .

psalm: ## Runs Psalm static analysis tool
	@mkdir -p build
	@@bin/console cache:clear $(ANSI_FLAG)
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --report=./build/psalm.json $(PSALM_COLOR_FLAG)
	$(call REPORT_PATHS,JSON report: build/psalm.json,build/psalm.json)

psalm-shepherd: ## Runs Psalm static analysis tool + report results to shepherd
	@mkdir -p build
	@@bin/console cache:clear $(ANSI_FLAG)
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --show-info=true --report=./build/psalm.json $(PSALM_COLOR_FLAG)
	$(call REPORT_PATHS,Local JSON report: build/psalm.json; Shepherd submission status is reported by Psalm; upload success is not verified,build/psalm.json)

psalm-github: ## Runs Psalm static analysis tool (GitHub)
	@mkdir -p build
	@@bin/console cache:clear $(ANSI_FLAG)
	@php ./vendor/bin/psalm --version
	@php ./vendor/bin/psalm --no-cache --shepherd --show-info=true --report=./build/psalm.json --output-format=github --monochrome
	$(call REPORT_PATHS,Local JSON report: build/psalm.json; Shepherd submission status is reported by Psalm; upload success is not verified,build/psalm.json)

phpstan: ## Runs PHPStan static analysis tool
	@@bin/console cache:clear $(ANSI_FLAG)
	@bin/console cache:clear $(ANSI_FLAG)
	@sleep 3
	@./tools/02_phpstan/vendor/phpstan/phpstan/phpstan --version
	@./tools/02_phpstan/vendor/phpstan/phpstan/phpstan -v $(ANSI_FLAG)

phpstan-github: ## Runs PHPStan static analysis tool (GitHub)
	@@bin/console cache:clear $(ANSI_FLAG)
	@bin/console cache:clear $(ANSI_FLAG)
	@sleep 3
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan -v --error-format=github --no-ansi

lint-configuration: ## Lint current defined `application.json` that it contains valid JSON
	@if php -r 'exit(json_decode(file_get_contents($$argv[1])) ? 0 : 1);' "$(APPLICATION_CONFIG)"; then \
		bash scripts/command-output.sh --status INFO "No errors in configuration file '$(APPLICATION_CONFIG)'"; \
	else \
		status=$$?; bash scripts/command-output.sh --status FAIL "Could not validate JSON in configuration file '$(APPLICATION_CONFIG)'"; exit $$status; \
	fi

lint-yaml: ## Lint config YAML files
	@@bin/console lint:yaml config --parse-tags $(ANSI_FLAG)

check-skills: ## Validate skill metadata, index coverage, and repository Markdown links inside Docker
	@bash scripts/command-output.sh "Skill metadata and documentation links" python3 scripts/check-skills.py

lint-workflows: ## Validate GitHub workflows, embedded shell, and local action metadata
	@bash scripts/command-output.sh "GitHub workflows and local action metadata" bash scripts/lint-workflows.sh

lint-markdown: ## Lint Markdown files
	@bash scripts/markdown-output.sh bash -lc 'if command -v npx >/dev/null 2>&1; then npx --yes markdownlint-cli2 "**/*.md" "!vendor/**" "!var/**" "!node_modules/**" "!tools/**/vendor/**" "!.git/**"; elif [ -f "$${HOME}/.nvm/nvm.sh" ]; then . "$${HOME}/.nvm/nvm.sh" && nvm use --silent default >/dev/null && npx --yes markdownlint-cli2 "**/*.md" "!vendor/**" "!var/**" "!node_modules/**" "!tools/**/vendor/**" "!.git/**"; else echo "npx is not available. Install Node.js/npm (or nvm with a default Node version)."; exit 127; fi'

clear: ## Clean vendor and tool dependencies
	$(call MUTATION_WARNING,Deleting vendor and tool vendor directories; dependencies must be reinstalled)
	@rm -rf vendor
	@${MAKE} clear-tools

clear-tools: ## Clears all tools dependencies
	$(call MUTATION_WARNING,Deleting vendor directories under tools; tool dependencies must be reinstalled)
	@find tools -type d -name vendor -prune -exec rm -rf -- {} +
	@bash scripts/command-output.sh --status INFO "Restart development containers after reinstalling dependencies"

check-dependencies-latest: ## Checks if any vendor dependency can be updated (latest versions)
	@bash scripts/command-output.sh "Vendor dependency update check (latest)" bin/console check-dependencies $(ANSI_FLAG)

check-dependencies-minor: ## Checks if any vendor dependency can be updated (only minor versions)
	@bash scripts/command-output.sh "Vendor dependency update check (minor)" bin/console check-dependencies --level=minor $(ANSI_FLAG)

check-dependencies-patch: ## Checks if any vendor dependency can be updated (only patch versions)
	@bash scripts/command-output.sh "Vendor dependency update check (patch)" bin/console check-dependencies --level=patch $(ANSI_FLAG)

check-licenses: ## Check vendor licenses
	@bash -o pipefail -c 'composer license --format=json | jq -r '"'"'.dependencies[] | .license | join(" OR ") | if length == 0 then "UNSPECIFIED" else . end'"'"' | sort | uniq -c | sort -rn'

update: ## Update composer dependencies
	$(call MUTATION_WARNING,Updating application dependencies and lockfiles; Composer hooks may update tools and application cache)
	@php -d memory_limit=-1 /usr/bin/composer update --with-all-dependencies --optimize-autoloader $(ANSI_FLAG)

update-tools: ## Update composer tools dependencies
	$(call MUTATION_WARNING,Updating dependency versions and lockfiles in tool groups)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all update --no-progress --optimize-autoloader $(ANSI_FLAG)

install-tools: ## Install composer tools dependencies
	$(call MUTATION_WARNING,Installing tool dependencies and rebuilding their autoloaders)
	@php -d memory_limit=-1 $(COMPOSER_BIN) bin all install --no-progress --optimize-autoloader $(ANSI_FLAG)

bash: ## Get bash inside PHP container
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,php,$(COMPOSE_EXEC))
	$(call STREAM_COMMAND,Opening Bash in the PHP container,env HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) $(COMPOSE_EXEC) exec php bash --rcfile /app/scripts/bashrc.sh -i)
endif

fish: ## Get fish inside PHP container
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,php,$(COMPOSE_EXEC))
	$(call STREAM_COMMAND,Opening Fish in the PHP container,env HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) $(COMPOSE_EXEC) exec php fish --init-command 'source /app/scripts/make-shell.fish')
endif

logs: ## Show logs from all containers
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,logs,docker compose)
	$(call STREAM_COMMAND,Following PHP nginx and MariaDB logs; Ctrl-C stops following,env HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose logs --follow php nginx mariadb)
endif

daemon: ## Start application in development mode in background
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,idle,docker compose)
	$(call MUTATION_WARNING,Starting development services; entrypoints may install dependencies generate keys and run migrations)
	$(call COMPOSE_RUN,Start development services in background,docker compose up --detach,Development containers started; readiness was not verified)
endif

daemon-build: ## Build containers and start application in development mode in background
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,idle,docker compose)
	$(call MUTATION_WARNING,Building and starting development services; entrypoints may install dependencies generate keys and run migrations)
	$(call COMPOSE_RUN,Build and start development services in background,docker compose up --build --detach,Development containers built and started; readiness was not verified)
endif

daemon-prod: ## Build and start application in local production mode in background
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,idle,$(COMPOSE_PROD))
	$(call MUTATION_WARNING,Building and starting local production; initialization may generate keys and run migrations; waiting for Compose health checks)
	$(call COMPOSE_RUN,Build and start local production; wait for Compose health checks,$(COMPOSE_PROD) up --build --detach --wait,Local production services passed Compose running/health checks; HTTP was not verified)
endif

start-prod: ## Build and start application in local production mode + watch output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,idle,$(COMPOSE_PROD))
	$(call MUTATION_WARNING,Building and attaching to local production; initialization may generate keys and run migrations; Ctrl-C requests shutdown)
	$(call COMPOSE_RUN,Build and attach to local production services,$(COMPOSE_PROD) up --build,Local production foreground session ended)
endif

stop: ## Stop application containers (development or local production)
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call MUTATION_WARNING,Stopping and removing application containers and networks; named volumes are retained)
	$(call COMPOSE_RUN,Stop application containers,docker compose down,Application containers stopped and removed; named volumes retained)
endif

start: ## Start application in development mode + watch output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,idle,docker compose)
	$(call MUTATION_WARNING,Starting and attaching to development services; entrypoints may install dependencies generate keys and run migrations; Ctrl-C requests shutdown)
	$(call COMPOSE_RUN,Start and attach to development services,docker compose up,Development foreground session ended; readiness was not verified)
endif

start-build: ## Build containers and start application in development mode + watch output
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call COMPOSE_CONTEXT,idle,docker compose)
	$(call MUTATION_WARNING,Building and attaching to development services; entrypoints may install dependencies generate keys and run migrations; Ctrl-C requests shutdown)
	$(call COMPOSE_RUN,Build and attach to development services,docker compose up --build,Development build and foreground session ended; readiness was not verified)
endif

local-configuration: ## Create local configuration files
	$(call MUTATION_WARNING,Overwriting .env.local and secrets/application.local.json with template configuration)
	@cp /app/.env /app/.env.local
	@cp /app/secrets/application.json /app/secrets/application.local.json
	@sed -i "s/application\.json/application\.local\.json/g" .env.local
	@bash scripts/command-output.sh --status INFO "Edit secrets/application.local.json for your local requirements"

composer-normalize: ## Normalizes `composer.json` file content
	$(call MUTATION_WARNING,Rewriting composer.json into normalized formatting)
	@composer normalize $(ANSI_FLAG)

composer-validate: ## Validate `composer.json` file content
	@composer validate --no-check-version $(ANSI_FLAG)

composer-require-checker: ## Check the defined dependencies against your code
	@XDEBUG_MODE=off php /app/vendor/bin/composer-require-checker $(ANSI_FLAG)

composer-unused: ## Show unused packages by scanning and comparing package namespaces against your source
	@XDEBUG_MODE=off php /app/vendor/bin/composer-unused $(ANSI_FLAG)

phploc: ## Runs `phploc` and create json output
	@mkdir -p build
	@php ./vendor/bin/phploc --log-json=./build/phploc.json ./src
	$(call REPORT_PATHS,JSON report: build/phploc.json,build/phploc.json)

check-security: ## Checks that application doesn't have installed dependencies with known security vulnerabilities
	@bash scripts/check-security.sh

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
	@bash scripts/action-update-output.sh ./scripts/check-action-updates.sh


project-stats: ## Create simple project stats
	@./scripts/project-stats.sh

ci-start: ## Build and start the Compose PHP development service for CI (host only)
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call MUTATION_WARNING,Building and starting only the CI PHP service; application initialization is disabled)
	@mkdir -p "$${COMPOSER_CACHE_DIR:-/tmp/symfony-backend-composer-cache}"
	$(call COMPOSE_RUN,Build and start the CI PHP service,$(COMPOSE_CI) up --build --detach --no-deps php,CI PHP service started; application initialization and readiness were not verified)
endif

ci-stop: ## Stop CI containers (host only)
ifeq ($(INSIDE_CONTAINER_RUNTIME), 1)
	$(HOST_CONTEXT_FAILURE)
else
	$(call MUTATION_WARNING,Stopping and removing CI containers and networks; named volumes are retained)
	$(call COMPOSE_RUN,Stop CI containers,$(COMPOSE_CI) down,CI containers stopped and removed; named volumes retained)
endif

install: ## Install project Composer dependencies
	$(call MUTATION_WARNING,Installing application dependencies and rebuilding autoloaders; Composer hooks may modify cache)
	@composer install --no-progress --optimize-autoloader $(ANSI_FLAG)

application-info: ## Display Symfony application information
	@bin/console about $(ANSI_FLAG)

check-requirements: ## Check application runtime requirements
	@bash scripts/command-output.sh "Application runtime requirements" php ./vendor/bin/requirements-checker

tool-versions: ## Display PHP, Composer, Symfony, and PHPUnit versions
	@php --version
	@bin/console --version $(ANSI_FLAG)
	@php ./vendor/bin/phpunit --version
	@composer --version $(ANSI_FLAG)

configure-ci: ## Configure the application and JWT keys for CI only
	@test "$$GITHUB_ACTIONS" = true || { bash scripts/command-output.sh --status FAIL "configure-ci is restricted to GitHub Actions."; exit 1; }
	$(call MUTATION_WARNING,Overwriting .env and .env.test from CI templates and replacing configured JWT keys)
	@cp .env.gh-actions .env
	@cp .env.gh-actions .env.test
	@$(MAKE) generate-jwt-keys

report-coveralls: ## Upload coverage to Coveralls
	@php ./vendor/bin/php-coveralls -v $(ANSI_FLAG)

prepare-sonar-reports: ## Copy test reports with relative paths for the host-side Sonar action
	@bash scripts/prepare-sonar-reports.sh

test-make-routing: ## Test host and CI container command routing
	@bash scripts/command-output.sh "Make container routing (offline mocked tests)" bash scripts/tests/make-container-test.sh

test-command-output: ## Run offline command-output contract regression suites
	@bash scripts/tests/command-output-contract-test.sh

test-docker-images: ## Run offline mocked helper tests; use docker-image-verify-pins to check actual Dockerfile pins
	@bash scripts/tests/docker-images-test.sh
endif
endif
