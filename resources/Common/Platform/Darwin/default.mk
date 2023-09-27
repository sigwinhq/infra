help: ## Prints this help
	@grep --no-filename --extended-regexp '^ *[-a-zA-Z0-9_/]+ *:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $$1, $$2}' | sort

DOCKER_CWD := ${CURDIR}
DOCKER_TTY := $(shell [ -t 0 ] && echo --tty)
DOCKER_USER := --user "$(shell id -u):$(shell id -g)"

define file_prefix
$(shell test -f ${2}${1} && echo -n ${2}${1} || echo ${1})
endef

define permissions

endef
