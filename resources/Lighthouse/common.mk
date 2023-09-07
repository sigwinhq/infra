LIGHTHOUSE_DOCKER_IMAGE ?= cypress/browsers:node-20.5.0-chrome-114.0.5735.133-1-ff-114.0.2-edge-114.0.1823.51-1
LIGHTHOUSE_DOCKER_COMMAND ?= docker run --init --interactive ${DOCKER_TTY} --rm --env HOME=/tmp ${DOCKER_USER} --volume "${DOCKER_CWD}:/public" --workdir "/public" ${LIGHTHOUSE_DOCKER_IMAGE}

analyze/lighthouse: ## Analyze built files using Lighthouse
	${LIGHTHOUSE_DOCKER_COMMAND} npx lhci autorun --config=lighthouse.config.json
