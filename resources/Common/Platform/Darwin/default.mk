help: ## Prints this help
	@grep --no-filename --extended-regexp '^ *[-a-zA-Z0-9_/]+ *:.*## ' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $$1, $$2}'

OS_CPUS:=$(shell sysctl -n hw.ncpu)

define permissions
	setfacl -dRm          m:rwX  $(1)
	setfacl -Rm           m:rwX  $(1)
	setfacl -dRm u:`whoami`:rwX  $(1)
	setfacl -Rm  u:`whoami`:rwX  $(1)
	setfacl -dRm u:${RUNNER}:rwX $(1)
	setfacl -Rm  u:${RUNNER}:rwX $(1)
	setfacl -dRm u:root:rwX      $(1)
	setfacl -Rm  u:root:rwX      $(1)
endef
