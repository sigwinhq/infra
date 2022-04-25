ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT}/PHP/common.mk

dist: cs check/phpstan check/psalm ## Prepare code for commit
check: check/cs check/phpstan check/psalm ## Analyze the codebase
