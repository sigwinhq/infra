ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/Node/common.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/Compose/common.mk

dist: analyze test ## Prepare the codebase for commit
analyze: analyze/lint ## Analyze the codebase
test: test/unit test/functional ## Test the codebase
test/unit: test/vitest ## Test the codebase, unit tests
test/functional: test/e2e ## Test the codebase, functional tests

clean: ## Clear logs and system cache
	rm -rf var/cache/* var/log/*

test/vitest:
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app npm test
test/e2e:
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app npm run test:e2e
analyze/lint:
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.${APP_ENV}.yaml exec ${DOCKER_USER} app npm run lint

setup/test: setup/filesystem ## Setup: create a functional test runtime
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app npm run setup:test

setup/filesystem: ${HOME}/.npm clean var/cache var/log ## Setup: filesystem (var, public/var folders)
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
