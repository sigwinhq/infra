ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading Secrets/common.mk)
endif
ifndef OS_FAMILY
include ${SIGWIN_INFRA_ROOT:%/=%}/Common/default.mk
endif

SECRETS_DIR ?= ./.infra/secrets
SECRETS_DIST ?= .dist

secrets: $(patsubst %${SECRETS_DIST},%,$(wildcard ${SECRETS_DIR}/*.secret${SECRETS_DIST}))
${SECRETS_DIR}:
	mkdir -p $@
${SECRETS_DIR}/%.secret: ${SECRETS_DIR}
	cp $@${SECRETS_DIST} $@
