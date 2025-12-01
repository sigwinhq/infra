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

# Check if a command is available, printing status with optional version
# Arguments: 1=command name, 2=required (1) or optional (0), 3=version command (optional)
define check_command
	if command -v $(1) >/dev/null 2>&1; then \
		version=""; \
		if [ -n "$(3)" ]; then \
			version=$$($(3) 2>/dev/null | head -1); \
		fi; \
		if [ -n "$$version" ]; then \
			printf "  \033[32m✓\033[0m %s \033[0;2m(%s)\033[0m\n" "$(1)" "$$version"; \
		else \
			printf "  \033[32m✓\033[0m %s\n" "$(1)"; \
		fi; \
	else \
		if [ "$(2)" = "1" ]; then \
			printf "  \033[31m✗\033[0m %s \033[31m(required)\033[0m\n" "$(1)"; \
			SIGWIN_INFRA_CHECK_FAILED=1; \
		else \
			printf "  \033[33m○\033[0m %s \033[0;2m(optional)\033[0m\n" "$(1)"; \
		fi; \
	fi
endef

# Check Docker Compose plugin availability
define check_docker_compose
	if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then \
		version=$$(docker compose version --short 2>/dev/null); \
		printf "  \033[32m✓\033[0m docker compose \033[0;2m(%s)\033[0m\n" "$$version"; \
	else \
		printf "  \033[31m✗\033[0m docker compose \033[31m(required)\033[0m\n"; \
		SIGWIN_INFRA_CHECK_FAILED=1; \
	fi
endef

# Check filesystem permissions on current directory
define check_filesystem
	printf "\n\033[1mFilesystem:\033[0m\n"; \
	if [ -w "$(CURDIR)" ]; then \
		printf "  \033[32m✓\033[0m Current directory is writable\n"; \
	else \
		printf "  \033[31m✗\033[0m Current directory is not writable\n"; \
		SIGWIN_INFRA_CHECK_FAILED=1; \
	fi; \
	if command -v getfacl >/dev/null 2>&1; then \
		printf "  \033[32m✓\033[0m ACL support available\n"; \
	else \
		printf "  \033[33m○\033[0m ACL support not available \033[0;2m(optional)\033[0m\n"; \
	fi
endef

# Check Infra version (local vs latest)
define check_infra_version
	printf "\n\033[1mInfra Version:\033[0m\n"; \
	LOCAL_VERSION=""; \
	if [ -f "$(ENTRYPOINT_DIR)/composer.lock" ] && command -v jq >/dev/null 2>&1; then \
		LOCAL_VERSION=$$(jq -r '.packages[] | select(.name == "sigwin/infra") | .version // empty' "$(ENTRYPOINT_DIR)/composer.lock" 2>/dev/null); \
		if [ -z "$$LOCAL_VERSION" ]; then \
			LOCAL_VERSION=$$(jq -r '.["packages-dev"][] | select(.name == "sigwin/infra") | .version // empty' "$(ENTRYPOINT_DIR)/composer.lock" 2>/dev/null); \
		fi; \
	fi; \
	if [ -z "$$LOCAL_VERSION" ] && [ -f "$(ENTRYPOINT_DIR)/package-lock.json" ] && command -v jq >/dev/null 2>&1; then \
		LOCAL_VERSION=$$(jq -r '.packages["node_modules/@sigwinhq/infra"].version // empty' "$(ENTRYPOINT_DIR)/package-lock.json" 2>/dev/null); \
	fi; \
	if [ -n "$$LOCAL_VERSION" ]; then \
		printf "  \033[32m✓\033[0m Local version: %s\n" "$$LOCAL_VERSION"; \
	else \
		printf "  \033[33m○\033[0m Local version: \033[0;2m(not detected)\033[0m\n"; \
	fi
endef

help/check: ## Check environment for sigwin/infra compatibility
	@$(call block_start,$@)
	@SIGWIN_INFRA_CHECK_FAILED=0; \
	printf "\n\033[1mMandatory Tools:\033[0m\n"; \
	$(call check_command,make,1,make --version); \
	$(call check_command,uname,1,uname -a); \
	$(call check_command,id,1,); \
	$(call check_command,echo,1,); \
	$(call check_command,test,1,); \
	$(call check_command,jq,1,jq --version); \
	$(call check_command,grep,1,grep --version); \
	$(call check_command,awk,1,awk --version); \
	$(call check_command,sort,1,sort --version); \
	$(call check_command,docker,1,docker --version); \
	$(call check_docker_compose); \
	printf "\n\033[1mOptional Tools:\033[0m\n"; \
	$(call check_command,mkcert,0,mkcert --version); \
	$(call check_command,setfacl,0,); \
	$(call check_filesystem); \
	$(call check_infra_version); \
	printf "\n"; \
	if [ "$$SIGWIN_INFRA_CHECK_FAILED" = "1" ]; then \
		printf "\033[31mEnvironment check failed. Please install missing required tools.\033[0m\n\n"; \
		exit 1; \
	else \
		printf "\033[32mEnvironment check passed.\033[0m\n\n"; \
	fi
	@$(call block_end)
