ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/Symfony/common.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/Compose/common.mk

dist: composer/normalize cs analyze/phpstan analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/composer analyze/cs analyze/phpstan analyze/psalm ## Analyze the codebase
test: test/unit test/functional ## Test the codebase
test/unit: test/infection ## Test the codebase, unit tests
test/functional: test/behat ## Test the codebase, functional tests

clean: ## Clear logs and system cache
	rm -rf var/cache/* var/log/*

test/behat:
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app vendor/bin/behat --colors --strict
setup/test: setup/filesystem ## Setup: create a functional test runtime
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction doctrine:database:drop --if-exists --force
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction doctrine:database:create
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction doctrine:migrations:migrate --all-or-nothing

setup/filesystem: ${HOME}/.composer clean var/cache var/log ## Setup: filesystem (var, public/var folders)
var:
	mkdir -p var
	$(call permissions,var)
var/cache: var
	mkdir -p var/cache
	$(call permissions,var/cache)
var/log: var
	mkdir -p var/log
	$(call permissions,var/log)
.PHONY: var/cache var/log
