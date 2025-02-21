ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading PHP/common.mk)
endif
ifndef OS_FAMILY
include ${SIGWIN_INFRA_ROOT:%/=%}/Common/default.mk
endif

ifndef NODE_VERSION
NODE_VERSION=21.7
endif

ifndef NODE_DOCKER_IMAGE
NODE_DOCKER_IMAGE=node:${NODE_VERSION}-alpine
endif

ifndef NODE_DOCKER_COMMAND
NODE_DOCKER_COMMAND=docker run --init --interactive ${DOCKER_TTY} --rm ${DOCKER_ENV} ${DOCKER_USER} --volume "$(DOCKER_CWD):$(DOCKER_CWD)" --volume "${HOME}/.npm:/home/node/.npm" --workdir "$(DOCKER_CWD)" ${NODE_DOCKER_IMAGE}
endif

sh/node: | ${HOME}/.npm ## Run Node shell
	${NODE_DOCKER_COMMAND} sh
${HOME}/.npm:
	mkdir -p ${HOME}/.npm
