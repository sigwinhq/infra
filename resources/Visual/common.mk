BACKSTOP_DOCKER_IMAGE ?= backstopjs/backstopjs:6.1.0
BACKSTOP_DOCKER_COMMAND ?= docker run --init --interactive ${TTY} --shm-size 256MB --cap-add=SYS_ADMIN --rm --env BASE_URL=${BASE_URL} --user "$(shell id -u):$(shell id -g)" --tmpfs /tmp --volume "$(shell pwd):${APP_ROOT}" --workdir ${APP_ROOT} ${BACKSTOP_DOCKER_IMAGE} --config backstop.config.js
visual/test:
	sh -c "${BACKSTOP_DOCKER_COMMAND} test"
visual/reference: ## Generate visual testing references
	sh -c "${BACKSTOP_DOCKER_COMMAND} reference"
