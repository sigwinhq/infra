ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/YASSG/common.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/PHP/common.mk

build/docker/imgproxy:
	docker compose up imgproxy -d
build/docker: build/docker/imgproxy ## Build app for "APP_ENV" target (defaults to "prod") fully in Docker
	docker compose run --rm webpack npm ci
	docker compose run --rm webpack npx encore production
	docker compose run --rm --env IMGPROXY_URL=http://imgproxy:8080 app vendor/sigwin/yassg/bin/yassg yassg:generate --env prod "$(BASE_URL)"
