ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/YASSG/common.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/Visual/common.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/Lighthouse/common.mk
include ${SIGWIN_INFRA_ROOT:%/=%}/PHP/common.mk

ifndef LYCHEE_DOCKER_IMAGE
LYCHEE_DOCKER_IMAGE=lycheeverse/lychee:0.13.0
endif

ifndef LYCHEE_DOCKER_COMMAND
LYCHEE_DOCKER_COMMAND=docker run --init --interactive ${DOCKER_TTY} --rm ${DOCKER_USER} --volume "$(DOCKER_CWD):$(DOCKER_CWD):ro" --workdir "$(DOCKER_CWD)" ${LYCHEE_DOCKER_IMAGE}
endif

dist: composer/normalize cs analyze/phpstan analyze/psalm test ## Prepare the codebase for commit
analyze: analyze/composer analyze/cs analyze/phpstan analyze/psalm ## Analyze the codebase
self/check: analyze/composer analyze/cs analyze/phpstan analyze/psalm check
ci/check: analyze/composer analyze/cs analyze/phpstan analyze/psalm lychee
test: visual/test ## Test the codebase

check: hack
	@make lychee

hack:
	# TODO: hack until this can be done natively by the tool
	php -r '$$u=rtrim(parse_url("${BASE_URL}",PHP_URL_PATH),"/");if($$u!==null&&$$u!==""&&$$u!=="/"){$$p=dirname("./${BUILD_DIR}".$$u);if(!file_exists($$p)){mkdir($$p,0755,true);}symlink(str_repeat("../",substr_count($$u,"/")-1)?:"./",$$p."/".basename($$u));}'

lychee:
	sh -c "${LYCHEE_DOCKER_COMMAND} --offline --base ./public ./public"
