ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading PHP/common.mk)
endif
include ${SIGWIN_INFRA_ROOT}/Common/default.mk

ifndef PHP_VERSION
PHP_VERSION=8.1
endif

ifndef TTY
TTY:=$(shell [ -t 0 ] && echo --tty)
endif

ifndef PHPQA_DOCKER_IMAGE
PHPQA_DOCKER_IMAGE=jakzal/phpqa:1.69.1-php${PHP_VERSION}-alpine
endif

ifndef PHPQA_DOCKER_COMMAND
PHPQA_DOCKER_COMMAND=docker run --init --interactive ${TTY} --rm --env "COMPOSER_CACHE_DIR=/composer/cache" --user "$(shell id -u):$(shell id -g)" --volume "$(shell pwd)/var/phpqa:/cache" --volume "$(shell pwd):/project" --volume "${HOME}/.composer:/composer" --workdir /project ${PHPQA_DOCKER_IMAGE}
endif

PHPSTAN_OUTPUT=
PSALM_OUTPUT=
define start
endef
define end
endef
ifdef GITHUB_ACTIONS
define start
echo ::group::$(1)
endef
define end
echo ::endgroup::
endef
PHPSTAN_OUTPUT=--error-format=github
PSALM_OUTPUT=--output-format=github
endif

sh/php: ${HOME}/.composer var/phpqa ## Run PHP shell
	sh -c "${PHPQA_DOCKER_COMMAND} sh"

composer/validate: ${HOME}/.composer var/phpqa
	sh -c "${PHPQA_DOCKER_COMMAND} composer validate --no-interaction"
composer/normalize: ${HOME}/.composer var/phpqa
	sh -c "${PHPQA_DOCKER_COMMAND} composer normalize --no-interaction --no-update-lock"
check/composer/normalize: ${HOME}/.composer var/phpqa
	sh -c "${PHPQA_DOCKER_COMMAND} composer normalize --no-interaction --no-update-lock --dry-run"

cs: ${HOME}/.composer var/phpqa
	sh -c "${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --diff -vvv"
check/cs: ${HOME}/.composer var/phpqa
	$(call start,PHP CS Fixer)
	sh -c "${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --dry-run --diff -vvv"
	$(call end)

check/phpstan: ${HOME}/.composer var/phpqa ## Analyze the codebase using PHPStan
	$(call start,PHPStan)
	sh -c "${PHPQA_DOCKER_COMMAND} phpstan analyse ${PHPSTAN_OUTPUT}"
	$(call end)

check/psalm: ${HOME}/.composer var/phpqa ## Analyze the codebase using Psalm
	$(call start,Psalm)
	sh -c "${PHPQA_DOCKER_COMMAND} psalm ${PSALM_OUTPUT}"
	$(call end)

${HOME}/.composer:
	mkdir -p ${HOME}/.composer
var/phpqa:
	mkdir -p var/phpqa