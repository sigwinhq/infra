ROOT=$(abspath $(dir $(lastword $(MAKEFILE_LIST))))
SHELL=bash
RUNNER=33

default: help
dist: composer/normalize cs phpstan psalm ## Prepare for commit
check: check/cs phpstan psalm

include ${ROOT}/../Common/Makefile
include ${ROOT}/../${OS_FAMILY}/Makefile

ifndef APP_ENV
include .env
endif

ifndef BUILD_ENV
BUILD_ENV=8.1
endif

ifndef TTY
TTY:=$(shell [ -t 0 ] && echo --tty)
endif

ifndef PHPQA_DOCKER_COMMAND
PHPQA_DOCKER_IMAGE=jakzal/phpqa:1.66.2-php${BUILD_ENV}-alpine
PHPQA_DOCKER_COMMAND=docker run --init --interactive ${TTY} --rm --env "COMPOSER_CACHE_DIR=/composer/cache" --user "$(shell id -u):$(shell id -g)" --volume "$(shell pwd)/var/tmp/phpqa:/tmp" --volume "$(shell pwd):/project" --volume "${HOME}/.composer:/composer" --workdir /project ${PHPQA_DOCKER_IMAGE}
endif

ifndef APP_DOCKER_COMMAND
APP_DOCKER_COMMAND=docker-compose exec --env "APP_ENV=$(value APP_ENV)" --user "$(shell id -u):$(shell id -g)" app
endif

composer/validate: util/ensure
	sh -c "${PHPQA_DOCKER_COMMAND} composer validate --no-interaction"
composer/normalize: util/ensure
	sh -c "${PHPQA_DOCKER_COMMAND} composer normalize --no-interaction"

cs: util/ensure ## Code style: fix the code style
	sh -c "${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --diff -vvv"
check/cs: util/ensure
	$(call start,PHP CS Fixer)
	sh -c "${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --dry-run --diff -vvv"
	$(call end)

phpstan: util/ensure ## Analyze the code using PHPStan
	$(call start,PHPStan)
	sh -c "${PHPQA_DOCKER_COMMAND} phpstan analyse ${PHPSTAN_OUTPUT}"
	$(call end)

psalm: util/ensure ## Analyze the code using Psalm
	$(call start,Psalm)
	sh -c "${PHPQA_DOCKER_COMMAND} psalm --threads=max --show-info=false --diff ${PSALM_OUTPUT}"
	$(call end)

start/dev: ## Start app in "dev" mode
	docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.dev.yaml up --detach --remove-orphans
start: ## Start app in APP_ENV mode (defined in .env)
	docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml up --detach --remove-orphans
stop: ## Stop app
	docker-compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml down --remove-orphans

sh/app: ## Run application shell
	sh -c "${APP_DOCKER_COMMAND} bash"
sh/php: util/ensure ## Run PHP shell
	sh -c "${PHPQA_DOCKER_COMMAND} sh"

setup/filesystem: util/ensure var/admin var/cache var/config var/log ## Setup: filesystem (var, public/var folders)
util/ensure: ${HOME}/.composer var/tmp/phpqa
${HOME}/.composer:
	mkdir -p ${HOME}/.composer
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
var/tmp/phpqa: var/tmp
	mkdir -p var/tmp/phpqa
	$(call permissions,var/tmp/phpqa)
