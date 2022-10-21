ifndef BASE_URL
$(error BASE_URL must be defined before loading Visual/common.mk)
endif

PROJECT_ROOT := $(shell pwd)

BACKSTOP_DOCKER_IMAGE ?= backstopjs/backstopjs:6.1.0
BACKSTOP_DOCKER_COMMAND ?= docker run --init --interactive ${TTY} --shm-size 256MB --cap-add=SYS_ADMIN --rm --env PROJECT_ROOT=${PROJECT_ROOT} --env BASE_URL=${BASE_URL} --user "$(shell id -u):$(shell id -g)" --tmpfs /tmp --volume "${PROJECT_ROOT}:${PROJECT_ROOT}" --workdir "${PROJECT_ROOT}" ${BACKSTOP_DOCKER_IMAGE} --config backstop.config.js
visual/test:
	sh -c "${BACKSTOP_DOCKER_COMMAND} test"
visual/reference: ## Generate visual testing references
	sh -c "${BACKSTOP_DOCKER_COMMAND} reference"
