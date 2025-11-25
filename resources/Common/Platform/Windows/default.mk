SHELL := pwsh.exe

COMMA := ,
EMPTY :=
SPACE := $(empty) $(empty)

# Cache the metadata file path (single shell invocation at load time)
# All other metadata is read at runtime by print_help_header to avoid multiple PowerShell startups
_METADATA_FILE := $(shell \
	$$ErrorActionPreference = 'SilentlyContinue'; \
	$$entrypointDir = "$(ENTRYPOINT_DIR)"; \
	if (Test-Path "$$entrypointDir/package.json") { \
		"$$entrypointDir/package.json" \
	} elseif (Test-Path "$$entrypointDir/composer.json") { \
		"$$entrypointDir/composer.json" \
	} \
)

# For backwards compatibility, these functions still work but are no longer used by print_help_header
# They are kept in case other parts of the codebase call them
define get_project_metadata
$(if $(_METADATA_FILE),$(shell \
	$$ErrorActionPreference = 'SilentlyContinue'; \
	$$json = Get-Content "$(_METADATA_FILE)" | ConvertFrom-Json; \
	$$value = $$json.$(1); \
	if ($$value) { $$value } \
))
endef

define get_nested_metadata
$(if $(_METADATA_FILE),$(shell \
	$$ErrorActionPreference = 'SilentlyContinue'; \
	$$json = Get-Content "$(_METADATA_FILE)" | ConvertFrom-Json; \
	$$value = $$json.$(1).$(2); \
	if ($$value) { $$value } \
))
endef

define get_infra_metadata
$(if $(_METADATA_FILE),$(shell \
	$$ErrorActionPreference = 'SilentlyContinue'; \
	$$json = Get-Content "$(_METADATA_FILE)" | ConvertFrom-Json; \
	$$value = $$json.extra.'sigwin/infra'.$(1); \
	if ($$value) { $$value } \
))
endef

# print_help_header reads all metadata from JSON at runtime (single shell invocation)
# This eliminates multiple PowerShell startups that occurred when using get_*_metadata functions
define print_help_header
	$$metadataFile = "$(_METADATA_FILE)"; \
	$$json = $$null; \
	if ($$metadataFile -and (Test-Path $$metadataFile)) { \
		$$json = Get-Content $$metadataFile | ConvertFrom-Json; \
	}; \
	$$PROJECT_NAME = if ($$env:PROJECT_NAME) { $$env:PROJECT_NAME } elseif ($$json -and $$json.name) { $$json.name } else { "" }; \
	$$PROJECT_DESCRIPTION = if ($$env:PROJECT_DESCRIPTION) { $$env:PROJECT_DESCRIPTION } elseif ($$json -and $$json.description) { $$json.description } else { "" }; \
	$$PROJECT_HOMEPAGE = if ($$env:PROJECT_HOMEPAGE) { $$env:PROJECT_HOMEPAGE } elseif ($$json -and $$json.homepage) { $$json.homepage } else { "" }; \
	$$PROJECT_REPOSITORY = if ($$env:PROJECT_REPOSITORY) { $$env:PROJECT_REPOSITORY } elseif ($$json -and $$json.repository -and $$json.repository.url) { $$json.repository.url } else { "" }; \
	if ([string]::IsNullOrEmpty($$PROJECT_REPOSITORY) -and $$json -and $$json.support -and $$json.support.source) { $$PROJECT_REPOSITORY = $$json.support.source }; \
	$$PROJECT_COLOR = "Magenta"; \
	if (-not [string]::IsNullOrEmpty($$PROJECT_NAME) -or -not [string]::IsNullOrEmpty($$PROJECT_DESCRIPTION)) { \
		Write-Host ""; \
		if (-not [string]::IsNullOrEmpty($$PROJECT_NAME)) { \
			Write-Host ("{0,-78}" -f $$PROJECT_NAME) -BackgroundColor $$PROJECT_COLOR -ForegroundColor White; \
		}; \
		if (-not [string]::IsNullOrEmpty($$PROJECT_DESCRIPTION)) { \
			Write-Host $$PROJECT_DESCRIPTION -ForegroundColor DarkGray; \
		}; \
		$$localUrlsEnv = $$env:PROJECT_LOCAL_URLS; \
		if ($$localUrlsEnv) { \
			Write-Host "Local:" -ForegroundColor DarkGray; \
			$$localUrlsEnv -split ',' | ForEach-Object { \
				$$parts = $$_ -split '\|', 2; \
				$$url = $$parts[0].Trim(); \
				$$desc = if ($$parts.Length -gt 1) { $$parts[1].Trim() } else { $$null }; \
				if ($$url) { \
					if ($$desc) { \
						Write-Host "  - $$url " -NoNewline; Write-Host "($$desc)" -ForegroundColor DarkGray; \
					} else { \
						Write-Host "  - $$url"; \
					} \
				} \
			}; \
		} elseif ($$json -and $$json.extra -and $$json.extra.'sigwin/infra' -and $$json.extra.'sigwin/infra'.local_urls) { \
			Write-Host "Local:" -ForegroundColor DarkGray; \
			$$json.extra.'sigwin/infra'.local_urls | ForEach-Object { \
				if ($$_ -is [string]) { \
					Write-Host "  - $$_"; \
				} else { \
					$$url = $$_.url; \
					$$desc = $$_.description; \
					if ($$url) { \
						if ($$desc) { \
							Write-Host "  - $$url " -NoNewline; Write-Host "($$desc)" -ForegroundColor DarkGray; \
						} else { \
							Write-Host "  - $$url"; \
						} \
					} \
				} \
			}; \
		}; \
		if (-not [string]::IsNullOrEmpty($$PROJECT_HOMEPAGE) -and -not [string]::IsNullOrEmpty($$PROJECT_REPOSITORY)) { \
			Write-Host "Homepage:   " -ForegroundColor DarkGray -NoNewline; Write-Host $$PROJECT_HOMEPAGE; \
		} elseif (-not [string]::IsNullOrEmpty($$PROJECT_HOMEPAGE)) { \
			Write-Host "Homepage: " -ForegroundColor DarkGray -NoNewline; Write-Host $$PROJECT_HOMEPAGE; \
		}; \
		if (-not [string]::IsNullOrEmpty($$PROJECT_REPOSITORY)) { \
			Write-Host "Repository: " -ForegroundColor DarkGray -NoNewline; Write-Host $$PROJECT_REPOSITORY; \
		}; \
		Write-Host ""; \
	}
endef

# TODO: rewrite output with https://learn.microsoft.com/en-us/powershell/module/microsoft.powershell.core/about/about_special_characters?view=powershell-7.3#escape-e
help: ## Prints this help
	@$(call print_help_header)
	@Select-String -Pattern '^ *(?<name>[-a-zA-Z0-9_/]+) *:.*## *(?<help>.+)' $(subst $(SPACE),${COMMA},$(strip $(subst .env,,${MAKEFILE_LIST}))) | Sort-Object {$$_.Matches[0].Groups["name"]} | ForEach-Object{"{0, -20}" -f $$_.Matches[0].Groups["name"] | Write-Host -NoNewline -BackgroundColor Magenta -ForegroundColor White; " {0}" -f $$_.Matches[0].Groups["help"] | Write-Host -ForegroundColor White}

# TODO: review
DOCKER_CWD := ${CURDIR}
DOCKER_TTY :=
DOCKER_USER :=

define file_prefix
${1}
endef

# TODO: review
define permissions
endef
