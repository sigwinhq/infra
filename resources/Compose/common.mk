ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading Compose/common.mk)
endif
ifndef OS_FAMILY
include ${SIGWIN_INFRA_ROOT:%/=%}/Common/default.mk
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/Secrets/common.mk

ifneq (,$(wildcard ./.env))
    include .env
    export
endif

VERSION ?= latest
APP_ENV ?= dev

build/dev: ## Build app for "dev" target
	VERSION=${VERSION} docker buildx bake --load --file docker-compose.yaml --file .infra/docker-buildx/docker-buildx.dev.hcl
build/prod: ## Build app for "prod" target
	VERSION=${VERSION} docker buildx bake --load --file docker-compose.yaml --file .infra/docker-buildx/docker-buildx.prod.hcl
registry/push:
	VERSION=${VERSION} docker compose push
registry/pull:
	VERSION=${VERSION} docker compose pull

start/dev: secrets ## Start app in "dev" mode
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.dev.yaml up --detach --remove-orphans --no-build
start/test: secrets ## Start app in "test" mode
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml up --detach --remove-orphans --no-build
start/prod: secrets ## Start app in "prod" mode
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.prod.yaml up --detach --remove-orphans --no-build
start: secrets ## Start app in APP_ENV mode (defined in .env)
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml up --detach --remove-orphans --no-build
stop: ## Stop app
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml down --remove-orphans

sh/app: ## Run application shell
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml exec ${DOCKER_USER} app sh
