ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/Common/default.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/Visual/common.mk

test: visual/test ## Test the codebase
