SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
include ${SIGWIN_INFRA_ROOT:%/=%}/PHP/library.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/YASSG/common.mk
