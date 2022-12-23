help: ## Prints this help
	@make -v
	@grep -h -E '^ *[-a-zA-Z0-9_/]+ *:.*## ' $(MAKEFILE_LIST)

OS_CPUS:=4

define file_prefix
	$(shell test -f ${2}${1} && echo -n ${2}${1} || echo ${1})
endef

# TODO: review
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
