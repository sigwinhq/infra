ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT}/PHP/common.mk

ifndef APP_DOCKER_COMMAND
APP_DOCKER_COMMAND=docker-compose exec --env "APP_ENV=$(value APP_ENV)" --user "$(shell id -u):$(shell id -g)" app
endif

dist: cs composer/normalize analyze/phpstan analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/composer analyze/cs analyze/phpstan analyze/psalm ## Analyze the codebase
test: test/phpunit-coverage ## Test the codebase

build/dev: ## Build app for "dev" target
	docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.dev.yaml build
build/prod: ## Build app for "prod" target
	docker-compose --file docker-compose.yaml build

start/dev: ## Start app in "dev" mode
	docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.dev.yaml up --detach --remove-orphans
start: ## Start app in APP_ENV mode (defined in .env)
	docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml up --detach --remove-orphans
stop: ## Stop app
	docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml down --remove-orphans

sh/app: ## Run application shell
	sh -c "${APP_DOCKER_COMMAND} bash"

clean: ## Clear application logs and system cache
	rm -rf var/admin/* var/cache/* var/log/*

setup/filesystem: ${HOME}/.composer public/var/assets public/var/tmp var/tmp var/admin var/cache var/config var/log var/versions ## Setup: filesystem (var, public/var folders)
public/var/assets:
	mkdir -p public/var/assets
	$(call permissions,public/var/assets)
public/var/tmp:
	mkdir -p public/var/tmp
	$(call permissions,public/var/tmp)
var:
	mkdir -p var
	$(call permissions,var)
var/admin: var
	mkdir -p var/admin
	$(call permissions,var/admin)
var/cache: var
	mkdir -p var/cache
	$(call permissions,var/cache)
var/config: var
	mkdir -p var/config
	$(call permissions,var/config)
var/log: var
	mkdir -p var/log
	$(call permissions,var/log)
var/tmp: var
	mkdir -p var/tmp
	$(call permissions,var/tmp)
var/versions: var
	mkdir -p var/versions
	$(call permissions,var/versions)
