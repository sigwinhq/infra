# Read all project metadata into shell variables
# Sets: PROJECT_NAME, PROJECT_DESCRIPTION, PROJECT_HOMEPAGE, PROJECT_REPOSITORY, PROJECT_COLOR, PROJECT_LOCAL_URLS
# PROJECT_LOCAL_URLS can be:
# - Set via environment variable (format: "url1|desc1,url2|desc2")
# - Read from JSON (local_urls array with url/description objects or plain strings)
define read_project_metadata
	PROJECT_NAME="$${PROJECT_NAME:-$(call get_project_metadata,name)}"; \
	PROJECT_DESCRIPTION="$${PROJECT_DESCRIPTION:-$(call get_project_metadata,description)}"; \
	PROJECT_HOMEPAGE="$${PROJECT_HOMEPAGE:-$(call get_project_metadata,homepage)}"; \
	PROJECT_REPOSITORY="$${PROJECT_REPOSITORY:-$(call get_nested_metadata,repository,url)}"; \
	if [ -z "$$PROJECT_REPOSITORY" ]; then PROJECT_REPOSITORY="$(call get_nested_metadata,support,source)"; fi; \
	PROJECT_COLOR="$${SIGWIN_INFRA_HELP_COLOR:-$(call get_infra_metadata,help_color)}"; \
	if [ -z "$$PROJECT_COLOR" ]; then PROJECT_COLOR="45"; fi; \
	if [ -z "$$PROJECT_LOCAL_URLS" ] && command -v jq >/dev/null 2>&1; then \
		if [ -f "$(ENTRYPOINT_DIR)/package.json" ]; then \
			PROJECT_LOCAL_URLS_JSON=$$(jq -c '.extra["sigwin/infra"].local_urls[]? // empty' "$(ENTRYPOINT_DIR)/package.json" 2>/dev/null); \
		elif [ -f "$(ENTRYPOINT_DIR)/composer.json" ]; then \
			PROJECT_LOCAL_URLS_JSON=$$(jq -c '.extra["sigwin/infra"].local_urls[]? // empty' "$(ENTRYPOINT_DIR)/composer.json" 2>/dev/null); \
		fi; \
	fi; \
	export PROJECT_NAME PROJECT_DESCRIPTION PROJECT_HOMEPAGE PROJECT_REPOSITORY PROJECT_COLOR PROJECT_LOCAL_URLS PROJECT_LOCAL_URLS_JSON
endef

define print_help_header
	$(call read_project_metadata); \
	if [ -n "$$PROJECT_NAME" ] || [ -n "$$PROJECT_DESCRIPTION" ]; then \
		echo ""; \
		if [ -n "$$PROJECT_NAME" ]; then \
			printf "\033[$${PROJECT_COLOR}m%-78s\033[0m\n" "$$PROJECT_NAME"; \
		fi; \
		if [ -n "$$PROJECT_DESCRIPTION" ]; then \
			printf "\033[0;2m%s\033[0m\n" "$$PROJECT_DESCRIPTION"; \
		fi; \
		if [ -n "$$PROJECT_LOCAL_URLS" ]; then \
			printf "\033[0;2mLocal:\033[0m\n"; \
			echo "$$PROJECT_LOCAL_URLS" | tr ',' '\n' | while IFS='|' read -r url desc; do \
				url=$$(echo "$$url" | xargs); \
				desc=$$(echo "$$desc" | xargs); \
				if [ -n "$$url" ]; then \
					if [ -n "$$desc" ]; then \
						printf "  - %s \033[0;2m(%s)\033[0m\n" "$$url" "$$desc"; \
					else \
						printf "  - %s\n" "$$url"; \
					fi; \
				fi; \
			done; \
		elif [ -n "$$PROJECT_LOCAL_URLS_JSON" ]; then \
			printf "\033[0;2mLocal:\033[0m\n"; \
			echo "$$PROJECT_LOCAL_URLS_JSON" | while IFS= read -r url_entry; do \
				if [ -n "$$url_entry" ]; then \
					if echo "$$url_entry" | jq -e 'type == "object"' >/dev/null 2>&1; then \
						url=$$(echo "$$url_entry" | jq -r '.url // empty'); \
						desc=$$(echo "$$url_entry" | jq -r '.description // empty'); \
						if [ -n "$$url" ]; then \
							if [ -n "$$desc" ]; then \
								printf "  - %s \033[0;2m(%s)\033[0m\n" "$$url" "$$desc"; \
							else \
								printf "  - %s\n" "$$url"; \
							fi; \
						fi; \
					else \
						url=$$(echo "$$url_entry" | jq -r '.'); \
						if [ -n "$$url" ]; then printf "  - %s\n" "$$url"; fi; \
					fi; \
				fi; \
			done; \
		fi; \
		if [ -n "$$PROJECT_HOMEPAGE" ] && [ -n "$$PROJECT_REPOSITORY" ]; then \
			printf "\033[0;2mHomepage:\033[0m   %s\n" "$$PROJECT_HOMEPAGE"; \
		elif [ -n "$$PROJECT_HOMEPAGE" ]; then \
			printf "\033[0;2mHomepage:\033[0m %s\n" "$$PROJECT_HOMEPAGE"; \
		fi; \
		if [ -n "$$PROJECT_REPOSITORY" ]; then \
			printf "\033[0;2mRepository:\033[0m %s\n" "$$PROJECT_REPOSITORY"; \
		fi; \
		echo ""; \
	fi
endef

help: ## Prints this help
	@$(call print_help_header)
	@grep -h -E '^ *[-a-zA-Z0-9_/]+ *:.*## ' $(strip $(subst .env,,${MAKEFILE_LIST})) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $$1, $$2}' | sort

DOCKER_CWD := ${CURDIR}
DOCKER_TTY := $(shell [ -t 0 ] && echo --tty)
DOCKER_USER := --user "$(shell id -u):$(shell id -g)"


define file_prefix
$(shell test -f ${2}${1} && echo -n ${2}${1} || echo ${1})
endef

define permissions
	setfacl -dRm          m:rwX  $(1)
	setfacl -Rm           m:rwX  $(1)
	setfacl -dRm u:`whoami`:rwX  $(1)
	setfacl -Rm  u:`whoami`:rwX  $(1)
	setfacl -dRm u:${RUNNER}:rwX $(1)
	setfacl -Rm  u:${RUNNER}:rwX $(1)
	setfacl -dRm u:root:rwX      $(1)
	setfacl -Rm  u:root:rwX      $(1)
endef
