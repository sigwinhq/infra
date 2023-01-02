ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/PHP/common.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/Monorepo/common.mk

dist: cs all/composer/normalize all/analyze/phpstan all/analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/cs all/analyze/composer all/analyze/phpstan all/analyze/psalm ## Analyze the codebase
test: all/test/infection ## Test the codebase
