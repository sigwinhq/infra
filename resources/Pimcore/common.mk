ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading Pimcore/common.mk)
endif

include ${SIGWIN_INFRA_ROOT:%/=%}/PHP/common.mk
