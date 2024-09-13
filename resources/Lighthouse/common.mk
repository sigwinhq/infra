LIGHTHOUSE_DOCKER_IMAGE ?= cypress/browsers:node-20.17.0-chrome-128.0.6613.119-1-ff-130.0-edge-128.0.2739.63-1
LIGHTHOUSE_DOCKER_COMMAND ?= docker run --init --interactive ${DOCKER_TTY} --rm --env HOME=/tmp ${DOCKER_USER} --volume "${DOCKER_CWD}:/public" --workdir "/public" ${LIGHTHOUSE_DOCKER_IMAGE}

analyze/lighthouse: ## Analyze built files using Lighthouse
	${LIGHTHOUSE_DOCKER_COMMAND} npx lhci autorun --config=lighthouse.config.json
