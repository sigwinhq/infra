SHELL := pwsh.exe

COMMA := ,
EMPTY :=
SPACE := $(empty) $(empty)

# Cache the metadata file path (single shell invocation at load time)
# All other metadata is read at runtime by read_project_metadata to avoid multiple PowerShell startups
_METADATA_FILE := $(shell \
	$$ErrorActionPreference = 'SilentlyContinue'; \
	$$entrypointDir = "$(ENTRYPOINT_DIR)"; \
	if (Test-Path "$$entrypointDir/package.json") { \
		"$$entrypointDir/package.json" \
	} elseif (Test-Path "$$entrypointDir/composer.json") { \
		"$$entrypointDir/composer.json" \
	} \
)

# Read all project metadata into PowerShell variables
# Sets: $PROJECT_NAME, $PROJECT_DESCRIPTION, $PROJECT_HOMEPAGE, $PROJECT_REPOSITORY, $PROJECT_COLOR, $PROJECT_LOCAL_URLS_DATA
# PROJECT_LOCAL_URLS_DATA contains an array of PSCustomObject with url and description properties
define read_project_metadata
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
	$$PROJECT_LOCAL_URLS_DATA = @(); \
	if ($$env:PROJECT_LOCAL_URLS) { \
		$$PROJECT_LOCAL_URLS_DATA = $$env:PROJECT_LOCAL_URLS -split ',' | ForEach-Object { \
			$$parts = $$_ -split '\|', 2; \
			[PSCustomObject]@{ url = $$parts[0].Trim(); description = if ($$parts.Count -gt 1) { $$parts[1].Trim() } else { "" } } \
		}; \
	} elseif ($$json -and $$json.extra -and $$json.extra.'sigwin/infra' -and $$json.extra.'sigwin/infra'.local_urls) { \
		$$PROJECT_LOCAL_URLS_DATA = $$json.extra.'sigwin/infra'.local_urls | ForEach-Object { \
			if ($$_ -is [string]) { [PSCustomObject]@{ url = $$_; description = "" } } else { [PSCustomObject]@{ url = $$_.url; description = if ($$_.description) { $$_.description } else { "" } } } \
		}; \
	}
endef

define print_help_header
	$(call read_project_metadata); \
	if (-not [string]::IsNullOrEmpty($$PROJECT_NAME) -or -not [string]::IsNullOrEmpty($$PROJECT_DESCRIPTION)) { \
		Write-Host ""; \
		if (-not [string]::IsNullOrEmpty($$PROJECT_NAME)) { \
			Write-Host ("{0,-78}" -f $$PROJECT_NAME) -BackgroundColor $$PROJECT_COLOR -ForegroundColor White; \
		}; \
		if (-not [string]::IsNullOrEmpty($$PROJECT_DESCRIPTION)) { \
			Write-Host $$PROJECT_DESCRIPTION -ForegroundColor DarkGray; \
		}; \
		if ($$PROJECT_LOCAL_URLS_DATA -and $$PROJECT_LOCAL_URLS_DATA.Count -gt 0) { \
			Write-Host "Local:" -ForegroundColor DarkGray; \
			$$PROJECT_LOCAL_URLS_DATA | ForEach-Object { \
				if (-not [string]::IsNullOrEmpty($$_.description)) { \
					Write-Host ("  - {0} " -f $$_.url) -NoNewline; Write-Host ("({0})" -f $$_.description) -ForegroundColor DarkGray; \
				} else { \
					Write-Host ("  - {0}" -f $$_.url); \
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
