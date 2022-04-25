# ${ROOT} is defined by the caller makefile
ifndef ROOT
$(error ROOT must be defined when loading Common/default.mk)
endif

SHELL := bash
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

include ${ROOT}/Common/Platform/${OS_FAMILY}/default.mk
