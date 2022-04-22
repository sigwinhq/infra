ROOT := $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST))))))
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

include ${ROOT}/../${OS_FAMILY}/Makefile
