SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
include ${SIGWIN_INFRA_ROOT}/PHP/default.mk

dist: cs check/phpstan check/psalm ## Prepare code for commit
check: check/cs check/phpstan check/psalm ## Analyze the codebase
