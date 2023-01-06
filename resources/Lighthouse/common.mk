LIGHTHOUSE_DOCKER_IMAGE ?= cypress/browsers:node18.12.0-chrome107
LIGHTHOUSE_DOCKER_COMMAND ?= docker run --init --interactive ${DOCKER_TTY} --rm --env HOME=/tmp ${DOCKER_USER} --volume "${DOCKER_CWD}:/project:ro" --workdir /project ${LIGHTHOUSE_DOCKER_IMAGE}

analyze/lighthouse: ## Analyze built files using Lighthouse
	${LIGHTHOUSE_DOCKER_COMMAND} npx lhci autorun --config=lighthouse.config.json
