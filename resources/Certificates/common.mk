ifndef SIGWIN_INFRA_ROOT
$(error SIGWIN_INFRA_ROOT must be defined before loading Certificates/common.mk)
endif
ifndef OS_FAMILY
include ${SIGWIN_INFRA_ROOT:%/=%}/Common/default.mk
endif

CERTIFICATES_DIR ?= ./.infra/certificates
_CERTIFICATES := $(addprefix ${CERTIFICATES_DIR}/,$(addsuffix .pem,$(_LOCAL_DOMAINS)))

certificates: $(_CERTIFICATES)
${CERTIFICATES_DIR}/%.pem:
	(cd ${CERTIFICATES_DIR} && mkcert $*)
