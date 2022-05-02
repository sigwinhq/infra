SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
include ${SIGWIN_INFRA_ROOT}/PHP/library.mk
include ${SIGWIN_INFRA_ROOT}/YASSG/common.mk

ifndef LYCHEE_DOCKER_IMAGE
LYCHEE_DOCKER_IMAGE=lycheeverse/lychee:20220118134522908286
endif

ifndef LYCHEE_DOCKER_COMMAND
LYCHEE_DOCKER_COMMAND=docker run --init --interactive ${TTY} --rm --user "$(shell id -u):$(shell id -g)" --volume "$(shell pwd):/project:ro" --workdir /project ${LYCHEE_DOCKER_IMAGE}
endif

self/check: analyze/composer analyze/cs analyze/phpstan analyze/psalm check
ci/check: analyze/composer analyze/cs analyze/phpstan analyze/psalm lychee

check: hack
	@make lychee

hack:
	# TODO: hack until this can be done natively by the tool
	php -r '$$u=rtrim(parse_url("${BASE_URL}",PHP_URL_PATH),"/");if($$u!==null&&$$u!==""&&$$u!=="/"){$$p=dirname("./${BUILD_DIR}".$$u);if(!file_exists($$p)){mkdir($$p,0755,true);}symlink(str_repeat("../",substr_count($$u,"/")-1)?:"./",$$p."/".basename($$u));}'

lychee:
	sh -c "${LYCHEE_DOCKER_COMMAND} --offline --base ./public ./public"
