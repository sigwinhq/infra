ROOT=$(abspath $(dir $(lastword $(MAKEFILE_LIST))))
SHELL=bash
RUNNER=33

default: help

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

composer/validate: util/ensure ## Composer: validate composer.* files
	sh -c "${PHPQA_DOCKER_COMMAND} composer validate --no-interaction"
composer/normalize: util/ensure ## Composer: normalize composer.* files
	sh -c "${PHPQA_DOCKER_COMMAND} composer normalize --no-interaction"

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
