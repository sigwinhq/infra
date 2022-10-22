ifndef MONOREPO_ROOT
$(error MONOREPO_ROOT must be defined before loading Monorepo/common.mk)
endif

ifndef MONOREPO_DIRS
$(error MONOREPO_DIRS must be defined before loading Monorepo/common.mk)
endif

all/%: %
	$(foreach DIR,${MONOREPO_DIRS},MONOREPO_ROOT=${CURDIR} $(MAKE) -C ${CURDIR}/${DIR} -f ${CURDIR}/Makefile $<;)
