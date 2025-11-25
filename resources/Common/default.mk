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

# Get the directory of the entrypoint Makefile (first in MAKEFILE_LIST)
ENTRYPOINT_DIR := $(patsubst %/,%,$(dir $(abspath $(firstword ${MAKEFILE_LIST}))))

# Extract metadata from package.json or composer.json
define get_project_metadata
$(shell \
	if command -v jq >/dev/null 2>&1; then \
		if [ -f "$(ENTRYPOINT_DIR)/package.json" ]; then \
			jq -r '.$(1) // empty' "$(ENTRYPOINT_DIR)/package.json" 2>/dev/null; \
		elif [ -f "$(ENTRYPOINT_DIR)/composer.json" ]; then \
			jq -r '.$(1) // empty' "$(ENTRYPOINT_DIR)/composer.json" 2>/dev/null; \
		fi; \
	fi \
)
endef

# Extract nested metadata from package.json or composer.json
define get_nested_metadata
$(shell \
	if command -v jq >/dev/null 2>&1; then \
		if [ -f "$(ENTRYPOINT_DIR)/package.json" ]; then \
			jq -r '.$(1).$(2) // empty' "$(ENTRYPOINT_DIR)/package.json" 2>/dev/null; \
		elif [ -f "$(ENTRYPOINT_DIR)/composer.json" ]; then \
			jq -r '.$(1).$(2) // empty' "$(ENTRYPOINT_DIR)/composer.json" 2>/dev/null; \
		fi; \
	fi \
)
endef

# Extract infra metadata from package.json or composer.json
# For composer.json, the infra config is in extra."sigwin/infra"
# For package.json, the infra config is in extra."sigwin/infra"
define get_infra_metadata
$(shell \
	if command -v jq >/dev/null 2>&1; then \
		if [ -f "$(ENTRYPOINT_DIR)/package.json" ]; then \
			jq -r '.extra["sigwin/infra"].$(1) // empty' "$(ENTRYPOINT_DIR)/package.json" 2>/dev/null; \
		elif [ -f "$(ENTRYPOINT_DIR)/composer.json" ]; then \
			jq -r '.extra["sigwin/infra"].$(1) // empty' "$(ENTRYPOINT_DIR)/composer.json" 2>/dev/null; \
		fi; \
	fi \
)
endef

include ${SIGWIN_INFRA_ROOT:%/=%}/Common/Platform/${OS_FAMILY}/default.mk

init:
	$(foreach MAKEFILE, $(call str_reverse, $(filter $(SIGWIN_INFRA_ROOT)%,$(subst .env,,${MAKEFILE_LIST}))),$(call dir_copy,$(basename $(abspath ${MAKEFILE}))))
