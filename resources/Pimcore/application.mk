ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/Pimcore/common.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/Compose/common.mk

dist: composer/normalize cs analyze/phpstan analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/composer analyze/cs analyze/phpstan analyze/psalm ## Analyze the codebase
test: test/unit test/functional ## Test the codebase
test/unit: test/infection ## Test the codebase, unit tests
test/functional: test/behat ## Test the codebase, functional tests

clean: ## Clear logs and system cache
	rm -rf var/admin/* var/cache/* var/log/* var/tmp/*

test/behat:
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app vendor/bin/behat --colors --strict
setup/test: ## Setup: create a functional test runtime
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction doctrine:database:drop --if-exists --force
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction doctrine:database:create
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app vendor/bin/pimcore-install --env test --no-interaction --skip-database-config
	VERSION=${VERSION} docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.test.yaml exec ${DOCKER_USER} app bin/console --env test --no-interaction sigwin:testing:setup

setup/filesystem: ${HOME}/.composer clean config/pimcore/classes public/var/assets public/var/tmp var/admin var/application-logger var/cache var/config var/email var/log var/tmp var/versions ## Setup: filesystem (var, public/var folders)
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
.PHONY: config/pimcore/classes public/var/assets public/var/tmp var/admin var/application-logger var/cache var/config var/email var/log var/tmp var/versions
