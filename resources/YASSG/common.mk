ifndef APP_ROOT
$(error APP_ROOT must be defined before loading YASSG/common.mk)
endif

APP_PORT ?= 9988
BUILD_DIR ?= public
BUILD_OPTS ?=
BASE_URL ?= file://localhost${APP_ROOT}/${BUILD_DIR}

start: dev ## Start app in APP_ENV mode (defaults to "dev")
start/dev: dev ## Start app in "dev" mode
dev: clean
	@make dev/assets dev/server dev/compose -j3
dev/server: vendor index.php
	YASSG_SKIP_BUNDLES=${YASSG_SKIP_BUNDLES} symfony server:start --no-tls --document-root=. --port=${APP_PORT}
dev/assets: node_modules
	node_modules/.bin/encore dev-server
dev/compose:
	docker compose up
index.php:
	ln -s vendor/sigwin/yassg/web/index.php

build: ${BUILD_DIR}/assets/entrypoints.json vendor ## Build app for "APP_ENV" target (defaults to "prod")
	YASSG_SKIP_BUNDLES=${YASSG_SKIP_BUNDLES} php vendor/sigwin/yassg/bin/yassg yassg:generate --env prod "$(BASE_URL)" ${BUILD_OPTS}
.PHONY: build

${BUILD_DIR}:
	mkdir -p ${BUILD_DIR}
${BUILD_DIR}/assets/entrypoints.json: | ${BUILD_DIR} node_modules
	BASE_URL=${BASE_URL} node_modules/.bin/encore production

clean:
	rm -rf var/cache/* var/log/* ${BUILD_DIR}
.PHONY: clean
node_modules:
	npm install
vendor:
	composer install
