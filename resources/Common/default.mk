ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading Common/default.mk)
endif

.DEPRECATED:
	$(warning NOTE: target "${DEPRECATED_FROM}" has been deprecated, use "${DEPRECATED_TO}" instead.)

define block_start
endef
define block_end
endef
ifdef GITHUB_ACTIONS
define block_start
echo ::group::$(1)
endef
define block_end
echo ::endgroup::
endef
endif

SHELL := sh
OS_FAMILY :=
ifeq ($(OS),Windows_NT)
	OS_FAMILY = Windows
else
	UNAME_S := $(shell uname -s)
	ifeq ($(UNAME_S),Linux)
		OS_FAMILY = Linux
	endif
	ifeq ($(UNAME_S),Darwin)
		OS_FAMILY = Darwin
	endif
endif

define dir_copy
$(shell if [ -d ${1} ]; then echo "cp -a ${1}/. ."; fi)
endef

define str_reverse
$(if $(wordlist 2,2,$(1)),$(call str_reverse,$(wordlist 2,$(words $(1)),$(1))) $(firstword $(1)),$(1))
endef

include ${SIGWIN_INFRA_ROOT:%/=%}/Common/Platform/${OS_FAMILY}/default.mk

init:
	$(foreach MAKEFILE, $(call str_reverse, ${MAKEFILE_LIST}), $(call dir_copy,$(basename ${MAKEFILE})))
