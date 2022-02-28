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

PHPSTAN_OUTPUT=
PSALM_OUTPUT=
define start
endef
define end
endef
ifdef GITHUB_ACTIONS
define start
echo ::group::$(1)
endef
define end
echo ::endgroup::
endef
PHPSTAN_OUTPUT=--error-format=github
PSALM_OUTPUT=--output-format=github
endif
