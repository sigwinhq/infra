.SILENT:
export PROJECT_NAME := Example Makefile Project
export PROJECT_DESCRIPTION := Example project using Makefile variables for help metadata
export PROJECT_HOMEPAGE := https://example.com/makefile
export PROJECT_REPOSITORY := https://github.com/example/makefile-project
export SIGWIN_INFRA_HELP_COLOR := 42
export PROJECT_LOCAL_URLS := http://localhost:9000|Dev server,http://localhost:9001|Admin panel

SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))../../resources
include ${SIGWIN_INFRA_ROOT:%/=%}/Common/default.mk
