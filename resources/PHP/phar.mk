ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/PHP/common.mk

dist: composer/normalize cs analyze/phpstan analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/composer analyze/cs analyze/phpstan analyze/psalm ## Analyze the codebase
test: test/infection ## Test the codebase

phar/build: | ${HOME}/.composer var/phpqa composer.lock ## Build PHAR file
	$(call block_start,$@)
	${PHPQA_DOCKER_COMMAND} box compile
	$(call block_end)
