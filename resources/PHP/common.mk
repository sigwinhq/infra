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
PHPQA_DOCKER_IMAGE=jakzal/phpqa:1.78.0-php${PHP_VERSION}-alpine
endif

ifndef PHPQA_DOCKER_COMMAND
PHPQA_DOCKER_COMMAND=docker run --init --interactive ${TTY} --rm --env "COMPOSER_CACHE_DIR=/composer/cache" --user "$(shell id -u):$(shell id -g)" --volume "$(shell pwd)/var/phpqa:/cache" --volume "$(shell pwd):/project" --volume "${HOME}/.composer:/composer" --workdir /project ${PHPQA_DOCKER_IMAGE}
endif

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
endif

sh/php: | ${HOME}/.composer var/phpqa composer.lock ## Run PHP shell
	sh -c "${PHPQA_DOCKER_COMMAND} sh"

composer/install: composer.lock
composer/install-highest: composer.lock
composer.lock: | ${HOME}/.composer var/phpqa
	sh -c "${PHPQA_DOCKER_COMMAND} composer install"
	touch composer.lock
composer/install-lowest: ${HOME}/.composer var/phpqa
	sh -c "${PHPQA_DOCKER_COMMAND} composer upgrade --prefer-lowest"

composer/validate: | ${HOME}/.composer var/phpqa composer.lock
	sh -c "${PHPQA_DOCKER_COMMAND} composer validate --no-interaction"
composer/normalize: | ${HOME}/.composer var/phpqa composer.lock
	sh -c "${PHPQA_DOCKER_COMMAND} composer normalize --no-interaction --no-update-lock"
analyze/composer: | ${HOME}/.composer var/phpqa composer.lock
	sh -c "${PHPQA_DOCKER_COMMAND} composer normalize --no-interaction --no-update-lock --dry-run"

cs: | ${HOME}/.composer var/phpqa composer.lock
	sh -c "${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --diff -vvv"
analyze/cs: | ${HOME}/.composer var/phpqa composer.lock
	$(call start,PHP CS Fixer)
	sh -c "${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --dry-run --diff -vvv"
	$(call end)

analyze/phpstan: | ${HOME}/.composer var/phpqa composer.lock
	$(call start,PHPStan)
	sh -c "${PHPQA_DOCKER_COMMAND} phpstan analyse --configuration $(call file_prefix,phpstan.neon.dist,$(PHP_VERSION)-)"
	$(call end)

analyze/psalm: | ${HOME}/.composer var/phpqa composer.lock
	$(call start,Psalm)
	sh -c "${PHPQA_DOCKER_COMMAND} psalm --php-version=${PHP_VERSION} --config $(call file_prefix,psalm.xml.dist,$(PHP_VERSION)-)"
	$(call end)

test/phpunit:
	$(call start,PHPUnit)
	sh -c "${PHPQA_DOCKER_COMMAND} vendor/bin/phpunit --verbose"
	$(call end)
test/phpunit-coverage: | ${HOME}/.composer var/phpqa composer.lock
	$(call start,PHPUnit)
	sh -c "${PHPQA_DOCKER_COMMAND} php -d pcov.enabled=1 vendor/bin/phpunit --verbose --coverage-text --log-junit=var/phpqa/phpunit/junit.xml --coverage-xml var/phpqa/phpunit/coverage-xml/"
	$(call end)
test/infection: test/phpunit-coverage
	$(call start,Infection)
	sh -c "${PHPQA_DOCKER_COMMAND} infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/phpqa/phpunit/ --threads ${OS_CPUS}"
	$(call end)

${HOME}/.composer:
	mkdir -p ${HOME}/.composer
var/phpqa:
	mkdir -p var/phpqa
