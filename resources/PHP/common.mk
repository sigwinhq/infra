ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading PHP/common.mk)
endif
ifndef OS_FAMILY
include ${SIGWIN_INFRA_ROOT:%/=%}/Common/default.mk
endif

ifndef PHP_VERSION
PHP_VERSION=8.4
endif

ifndef PHPQA_DOCKER_IMAGE
PHPQA_DOCKER_IMAGE=jakzal/phpqa:1.112.1-php${PHP_VERSION}-alpine
endif

ifndef PHPQA_DOCKER_COMMAND
PHPQA_DOCKER_COMMAND=docker run --init --interactive ${DOCKER_TTY} --rm ${DOCKER_ENV} --env "COMPOSER_CACHE_DIR=/composer/cache" ${DOCKER_USER} --volume "$(DOCKER_CWD)/var/phpqa:/cache" --volume "$(DOCKER_CWD):$(DOCKER_CWD)" --volume "${HOME}/.composer:/composer" --workdir "$(DOCKER_CWD)" ${PHPQA_DOCKER_IMAGE}
endif

sh/php: | ${HOME}/.composer var/phpqa composer.lock ## Run PHP shell
	${PHPQA_DOCKER_COMMAND} sh

composer/install: composer.lock
composer.lock: | ${HOME}/.composer var/phpqa
	${PHPQA_DOCKER_COMMAND} composer install --audit
	touch composer.lock
composer/install-lowest: ${HOME}/.composer var/phpqa
	${PHPQA_DOCKER_COMMAND} composer upgrade --prefer-lowest
composer/install-highest: ${HOME}/.composer var/phpqa
	${PHPQA_DOCKER_COMMAND} composer upgrade

composer/validate: | ${HOME}/.composer var/phpqa composer.lock
	${PHPQA_DOCKER_COMMAND} composer validate --no-interaction
composer/normalize: | ${HOME}/.composer var/phpqa composer.lock
	${PHPQA_DOCKER_COMMAND} composer normalize --no-interaction --no-update-lock
analyze/composer: | ${HOME}/.composer var/phpqa composer.lock
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} composer normalize --no-interaction --no-update-lock --dry-run
	$(call block_end)

cs: | ${HOME}/.composer var/phpqa composer.lock
	${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --diff -vvv
analyze/cs: | ${HOME}/.composer var/phpqa composer.lock
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --diff -vvv --dry-run
	$(call block_end)

analyze/phpstan: | ${HOME}/.composer var/phpqa composer.lock
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} phpstan analyse --configuration $(call file_prefix,phpstan.neon.dist,$(PHP_VERSION)-)
	$(call block_end)

analyze/psalm: | ${HOME}/.composer var/phpqa composer.lock
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} psalm --php-version=${PHP_VERSION} --config $(call file_prefix,psalm.xml.dist,$(PHP_VERSION)-)
	$(call block_end)

test/phpunit: | ${HOME}/.composer var/phpqa composer.lock
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} vendor/bin/phpunit
	$(call block_end)
test/phpunit-coverage: | ${HOME}/.composer var/phpqa composer.lock
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text --log-junit=var/phpqa/phpunit/junit.xml --coverage-xml var/phpqa/phpunit/coverage-xml/
	$(call block_end)
test/infection: test/phpunit-coverage
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} infection run --verbose --show-mutations --no-interaction --only-covered --only-covering-test-cases --coverage var/phpqa/phpunit/ --threads max
	$(call block_end)

${HOME}/.composer:
	mkdir -p ${HOME}/.composer
var/phpqa:
	mkdir -p var/phpqa
