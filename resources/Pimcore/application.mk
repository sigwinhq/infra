ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/Pimcore/common.mk

ifneq (,$(wildcard ./.env))
    include .env
    export
endif

APP_DOCKER_COMMAND ?= docker compose exec ${DOCKER_USER} app

VERSION ?= latest

BASE_URL ?= http://example.com/

SECRETS_DIR ?= ./.infra/secrets
SECRETS_DIST ?= .dist

dist: composer/normalize cs analyze/phpstan analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/composer analyze/cs analyze/phpstan analyze/psalm ## Analyze the codebase
test: test/unit test/functional ## Test the codebase
test/unit: test/infection ## Test the codebase, unit tests
test/functional: test/behat ## Test the codebase, functional tests

build/dev: ## Build app for "dev" target
	VERSION=${VERSION} docker buildx bake --load --file docker-compose.yaml --set *.args.BASE_URL=${BASE_URL} --file .infra/docker-buildx/docker-buildx.dev.hcl
build/prod: ## Build app for "prod" target
	VERSION=${VERSION} docker buildx bake --load --file docker-compose.yaml --set *.args.BASE_URL=${BASE_URL} --file .infra/docker-buildx/docker-buildx.prod.hcl
registry/push:
	VERSION=${VERSION} docker compose push
registry/pull:
	VERSION=${VERSION} docker compose pull

start/dev: secrets ## Start app in "dev" mode
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.dev.yaml up --detach --remove-orphans --no-build
start/prod: secrets ## Start app in "prod" mode
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.prod.yaml up --detach --remove-orphans --no-build
start: secrets ## Start app in APP_ENV mode (defined in .env)
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml up --detach --remove-orphans --no-build
stop: ## Stop app
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml down --remove-orphans

sh/app: ## Run application shell
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml exec ${DOCKER_USER} app sh

clean: ## Clear logs and system cache
	rm -rf var/admin/* var/cache/* var/log/* var/tmp/*

test/behat:
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app vendor/bin/behat --colors --strict
setup/test: ## Setup: create a functional test runtime
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction doctrine:database:drop --if-exists --force
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction doctrine:database:create
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app vendor/bin/pimcore-install --env test --no-interaction --skip-database-config
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction sigwin:testing:setup
start/test: secrets ## Start app in "test" mode
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml up --detach --remove-orphans --no-build

setup/filesystem: ${HOME}/.composer clean config/pimcore/classes public/var/assets public/var/tmp var/admin var/application-logger var/cache var/config var/email var/log var/tmp var/versions ## Setup: filesystem (var, public/var folders)
config/pimcore/classes:
	mkdir -p config/pimcore/classes
	$(call permissions,config/pimcore/classes)
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
var/application-logger: var
	mkdir -p var/application-logger
	$(call permissions,var/application-logger)
var/cache: var
	mkdir -p var/cache
	$(call permissions,var/cache)
var/config: var
	mkdir -p var/config
	$(call permissions,var/config)
var/email: var
	mkdir -p var/email
	$(call permissions,var/email)
var/log: var
	mkdir -p var/log
	$(call permissions,var/log)
var/tmp: var
	mkdir -p var/tmp
	$(call permissions,var/tmp)
var/versions: var
	mkdir -p var/versions
	$(call permissions,var/versions)
.PHONY: config/pimcore/classes public/var/assets public/var/tmp var/admin var/application-logger var/cache var/config var/email var/log var/tmp var/versions

secrets: $(patsubst %${SECRETS_DIST},%,$(wildcard ${SECRETS_DIR}/*.secret${SECRETS_DIST}))
${SECRETS_DIR}/%.secret:
	cp $@${SECRETS_DIST} $@
