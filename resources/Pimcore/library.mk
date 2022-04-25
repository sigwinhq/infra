ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))

include ${ROOT}/Common/default.mk
include ${ROOT}/PHP/default.mk
