ROOT=$(abspath $(dir $(lastword $(MAKEFILE_LIST))))
SHELL=bash
RUNNER=33

default: help
dist: composer/normalize cs phpstan psalm ## Prepare for commit
check: check/cs phpstan psalm

include ${ROOT}/../Common/Makefile
include ${ROOT}/../${OS_FAMILY}/Makefile
include ${ROOT}/../PHP/Makefile

ifndef APP_ENV
include .env
endif

ifndef APP_DOCKER_COMMAND
APP_DOCKER_COMMAND=docker-compose exec --env "APP_ENV=$(value APP_ENV)" --user "$(shell id -u):$(shell id -g)" app
endif

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
	rm -rf var/cache/* var/log/*

setup/filesystem: ${HOME}/.composer var/tmp var/admin var/cache var/config var/log ## Setup: filesystem (var, public/var folders)
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
