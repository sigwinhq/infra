ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/Pimcore/common.mk

ifndef COMPOSE_PROJECT_NAME
$(warning COMPOSE_PROJECT_NAME must be defined before loading Pimcore/library.mk to run functional tests)
endif
ifndef PIMCORE_KERNEL_CLASS
$(warning PIMCORE_KERNEL_CLASS must be defined before loading Pimcore/library.mk to run functional tests)
endif

TESTS_RUNTIME_ROOT?=tests/runtime

dist: composer/normalize cs analyze/phpstan analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/composer analyze/cs analyze/phpstan analyze/psalm ## Analyze the codebase
test/unit: test/infection ## Test the codebase, unit tests
test/functional: test/behat ## Test the codebase, functional tests
test: test/unit test/functional ## Test the codebase

ifneq ($(and $(COMPOSE_PROJECT_NAME),$(PIMCORE_KERNEL_CLASS)),)
start/test: ## Start app in "test" mode
	COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} docker compose --file ${TESTS_RUNTIME_ROOT}/docker-compose.yaml up --detach --remove-orphans --no-build
stop: ## Stop app
	COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} docker compose --file ${TESTS_RUNTIME_ROOT}/docker-compose.yaml down --remove-orphans
sh/app: ## Run application shell
	COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} docker compose --file ${TESTS_RUNTIME_ROOT}/docker-compose.yaml exec ${DOCKER_USER} --env PIMCORE_KERNEL_CLASS=${PIMCORE_KERNEL_CLASS} app sh
setup/test: start/test .env ## Setup: create a functional test runtime
	COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} docker compose --file ${TESTS_RUNTIME_ROOT}/docker-compose.yaml exec ${DOCKER_USER} --env PIMCORE_KERNEL_CLASS=${PIMCORE_KERNEL_CLASS} app php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:drop --if-exists --force
	COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} docker compose --file ${TESTS_RUNTIME_ROOT}/docker-compose.yaml exec ${DOCKER_USER} --env PIMCORE_KERNEL_CLASS=${PIMCORE_KERNEL_CLASS} app php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:create
	COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} docker compose --file ${TESTS_RUNTIME_ROOT}/docker-compose.yaml exec ${DOCKER_USER} --env PIMCORE_KERNEL_CLASS=${PIMCORE_KERNEL_CLASS} app vendor/bin/pimcore-install --env test --no-interaction --ignore-existing-config --skip-database-config
	COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} docker compose --file ${TESTS_RUNTIME_ROOT}/docker-compose.yaml exec ${DOCKER_USER} --env PIMCORE_KERNEL_CLASS=${PIMCORE_KERNEL_CLASS} app php tests/runtime/bootstrap.php --env test --no-interaction sigwin:testing:setup
test/behat:
	COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME} docker compose --file ${TESTS_RUNTIME_ROOT}/docker-compose.yaml exec ${DOCKER_USER} --env PIMCORE_KERNEL_CLASS=${PIMCORE_KERNEL_CLASS} app vendor/bin/behat --colors --strict
clean: ## Clear logs and system cache
	rm -rf var/ tests/runtime/var
.env:
	touch .env
else
test/behat:
endif

setup/pimcore: setup/test
	DEPRECATED_FROM=$@ DEPRECATED_TO=$< $(MAKE) --no-print-directory .DEPRECATED
