ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT}/PHP/common.mk

ifndef APP_DOCKER_COMMAND
APP_DOCKER_COMMAND=docker-compose exec --user "$(shell id -u):$(shell id -g)" app
endif

ifndef VERSION
VERSION=latest
endif

ifndef BASE_URL
BASE_URL=http://example.com/
endif

ifndef SECRETS_DIR
SECRETS_DIR=./.infra/secrets
endif
ifndef SECRETS_DIST
SECRETS_DIST=.dist
endif

dist: cs composer/normalize analyze/phpstan analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/composer analyze/cs analyze/phpstan analyze/psalm ## Analyze the codebase
test: test/phpunit-coverage ## Test the codebase

build/dev: ## Build app for "dev" target
	VERSION=${VERSION} docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.dev.yaml build --build-arg BASE_URL=${BASE_URL}
build/prod: ## Build app for "prod" target
	VERSION=${VERSION} docker-compose --file docker-compose.yaml build --build-arg BASE_URL=${BASE_URL}
registry/push:
	VERSION=${VERSION} docker-compose push
registry/pull:
	VERSION=${VERSION} docker-compose pull

start/dev: secrets ## Start app in "dev" mode
	VERSION=${VERSION} docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.dev.yaml up --detach --remove-orphans
start/prod: secrets ## Start app in "prod" mode
	VERSION=${VERSION} docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.prod.yaml up --detach --remove-orphans --no-build
start: secrets ## Start app in APP_ENV mode (defined in .env)
	VERSION=${VERSION} docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml up --detach --remove-orphans
stop: ## Stop app
	VERSION=${VERSION} docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml down --remove-orphans

sh/app: ## Run application shell
	VERSION=${VERSION} sh -c "${APP_DOCKER_COMMAND} sh"

clean: ## Clear application logs and system cache
	rm -rf var/admin/* var/cache/* var/log/* var/tmp/*

setup/filesystem: ${HOME}/.composer config/pimcore/classes public/var/assets public/var/tmp var/tmp var/admin var/application-logger var/cache var/config var/email var/log var/versions ## Setup: filesystem (var, public/var folders)
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

secrets: $(patsubst %${SECRETS_DIST},%,$(wildcard ${SECRETS_DIR}/*.secret${SECRETS_DIST}))
${SECRETS_DIR}/%.secret:
	cp $@${SECRETS_DIST} $@
