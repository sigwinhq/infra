LIGHTHOUSE_DOCKER_IMAGE ?= cypress/browsers:node-20.9.0-chrome-118.0.5993.88-1-ff-118.0.2-edge-118.0.2088.46-1
LIGHTHOUSE_DOCKER_COMMAND ?= docker run --init --interactive ${DOCKER_TTY} --rm --env HOME=/tmp ${DOCKER_USER} --volume "${DOCKER_CWD}:/public" --workdir "/public" ${LIGHTHOUSE_DOCKER_IMAGE}

analyze/lighthouse: ## Analyze built files using Lighthouse
	${LIGHTHOUSE_DOCKER_COMMAND} npx lhci autorun --config=lighthouse.config.json
