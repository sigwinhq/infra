ifndef MAKEFILE_LIST
$(error MAKEFILE_LIST must be defined, are you running GNU make?)
endif

ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading Common/default.mk)
endif

.DEFAULT_GOAL := help
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
	UNAME_R := $(shell uname -r)
	ifneq ($(findstring WSL2,$(UNAME_R)),)
		OS_FAMILY = Linux
	endif
	UNAME_R := $(shell uname -r)
	ifneq ($(findstring WSL2,$(UNAME_R)),)
		OS_FAMILY = Linux
	endif
endif

define dir_copy
if [ -d "${1}" ]; then cp -a ${1}/. .; fi

if [ -f .gitattributes.dist ]; then mv .gitattributes.dist .gitattributes; fi

endef

define str_reverse
$(if $(wordlist 2,2,$(1)),$(call str_reverse,$(wordlist 2,$(words $(1)),$(1))) $(firstword $(1)),$(1))
endef

include ${SIGWIN_INFRA_ROOT:%/=%}/Common/Platform/${OS_FAMILY}/default.mk

init:
	$(foreach MAKEFILE, $(call str_reverse, $(subst .env,,${MAKEFILE_LIST})),$(call dir_copy,$(basename $(abspath ${MAKEFILE}))))
