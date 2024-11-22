LIGHTHOUSE_DOCKER_IMAGE ?= cypress/browsers:node-22.11.0-chrome-131.0.6778.69-1-ff-132.0.2-edge-131.0.2903.51-1
LIGHTHOUSE_DOCKER_COMMAND ?= docker run --init --interactive ${DOCKER_TTY} --rm --env HOME=/tmp ${DOCKER_USER} --volume "${DOCKER_CWD}:/public" --workdir "/public" ${LIGHTHOUSE_DOCKER_IMAGE}

analyze/lighthouse: ## Analyze built files using Lighthouse
	${LIGHTHOUSE_DOCKER_COMMAND} npx lhci autorun --config=lighthouse.config.json
