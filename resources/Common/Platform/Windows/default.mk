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

# Check if a command is available in PowerShell
# Arguments: 1=command name, 2=required (1) or optional (0), 3=version command (optional)
define check_command_ps
	$$cmd = "$(1)"; \
	$$required = "$(2)"; \
	$$versionCmd = "$(3)"; \
	if (Get-Command $$cmd -ErrorAction SilentlyContinue) { \
		$$version = ""; \
		if ($$versionCmd) { \
			try { $$version = (Invoke-Expression $$versionCmd 2>$$null | Select-Object -First 1) } catch {} \
		}; \
		if ($$version) { \
			Write-Host "  " -NoNewline; Write-Host "✓" -ForegroundColor Green -NoNewline; Write-Host " $$cmd " -NoNewline; Write-Host "($$version)" -ForegroundColor DarkGray; \
		} else { \
			Write-Host "  " -NoNewline; Write-Host "✓" -ForegroundColor Green -NoNewline; Write-Host " $$cmd"; \
		} \
	} else { \
		if ($$required -eq "1") { \
			Write-Host "  " -NoNewline; Write-Host "✗" -ForegroundColor Red -NoNewline; Write-Host " $$cmd " -NoNewline; Write-Host "(required)" -ForegroundColor Red; \
			$$script:SIGWIN_INFRA_CHECK_FAILED = $$true; \
		} else { \
			Write-Host "  " -NoNewline; Write-Host "○" -ForegroundColor Yellow -NoNewline; Write-Host " $$cmd " -NoNewline; Write-Host "(optional)" -ForegroundColor DarkGray; \
		} \
	}
endef

# Check Docker Compose plugin availability in PowerShell
define check_docker_compose_ps
	if ((Get-Command docker -ErrorAction SilentlyContinue) -and (docker compose version 2>$$null)) { \
		$$version = (docker compose version --short 2>$$null); \
		Write-Host "  " -NoNewline; Write-Host "✓" -ForegroundColor Green -NoNewline; Write-Host " docker compose " -NoNewline; Write-Host "($$version)" -ForegroundColor DarkGray; \
	} else { \
		Write-Host "  " -NoNewline; Write-Host "✗" -ForegroundColor Red -NoNewline; Write-Host " docker compose " -NoNewline; Write-Host "(required)" -ForegroundColor Red; \
		$$script:SIGWIN_INFRA_CHECK_FAILED = $$true; \
	}
endef

# Check filesystem permissions in PowerShell
define check_filesystem_ps
	Write-Host ""; \
	Write-Host "Filesystem:" -ForegroundColor White; \
	$$testFile = Join-Path "$(CURDIR)" ".sigwin-infra-check-$$([guid]::NewGuid().ToString('N'))"; \
	try { \
		[System.IO.File]::WriteAllText($$testFile, "test"); \
		Remove-Item $$testFile -Force; \
		Write-Host "  " -NoNewline; Write-Host "✓" -ForegroundColor Green -NoNewline; Write-Host " Current directory is writable"; \
	} catch { \
		Write-Host "  " -NoNewline; Write-Host "✗" -ForegroundColor Red -NoNewline; Write-Host " Current directory is not writable"; \
		$$script:SIGWIN_INFRA_CHECK_FAILED = $$true; \
	}
endef

# Check Infra version in PowerShell
define check_infra_version_ps
	Write-Host ""; \
	Write-Host "Infra Version:" -ForegroundColor White; \
	$$localVersion = $$null; \
	$$composerLock = Join-Path "$(ENTRYPOINT_DIR)" "composer.lock"; \
	$$packageLock = Join-Path "$(ENTRYPOINT_DIR)" "package-lock.json"; \
	if (Test-Path $$composerLock) { \
		$$json = Get-Content $$composerLock | ConvertFrom-Json; \
		$$pkg = $$json.packages | Where-Object { $$_.name -eq "sigwin/infra" } | Select-Object -First 1; \
		if (-not $$pkg) { $$pkg = $$json."packages-dev" | Where-Object { $$_.name -eq "sigwin/infra" } | Select-Object -First 1 }; \
		if ($$pkg) { $$localVersion = $$pkg.version }; \
	}; \
	if (-not $$localVersion -and (Test-Path $$packageLock)) { \
		$$json = Get-Content $$packageLock | ConvertFrom-Json; \
		if ($$json.packages."node_modules/@sigwinhq/infra") { $$localVersion = $$json.packages."node_modules/@sigwinhq/infra".version }; \
	}; \
	if ($$localVersion) { \
		Write-Host "  " -NoNewline; Write-Host "✓" -ForegroundColor Green -NoNewline; Write-Host " Local version: $$localVersion"; \
	} else { \
		Write-Host "  " -NoNewline; Write-Host "○" -ForegroundColor Yellow -NoNewline; Write-Host " Local version: " -NoNewline; Write-Host "(not detected)" -ForegroundColor DarkGray; \
	}
endef

help/check: ## Check environment for sigwin/infra compatibility
	@$(call block_start,$@)
	@$$script:SIGWIN_INFRA_CHECK_FAILED = $$false; \
	Write-Host ""; \
	Write-Host "Mandatory Tools:" -ForegroundColor White; \
	$(call check_command_ps,make,1,make --version); \
	$(call check_command_ps,docker,1,docker --version); \
	$(call check_docker_compose_ps); \
	Write-Host ""; \
	Write-Host "Optional Tools:" -ForegroundColor White; \
	$(call check_command_ps,mkcert,0,mkcert --version); \
	$(call check_filesystem_ps); \
	$(call check_infra_version_ps); \
	Write-Host ""; \
	if ($$script:SIGWIN_INFRA_CHECK_FAILED) { \
		Write-Host "Environment check failed. Please install missing required tools." -ForegroundColor Red; \
		Write-Host ""; \
		exit 1; \
	} else { \
		Write-Host "Environment check passed." -ForegroundColor Green; \
		Write-Host ""; \
	}
	@$(call block_end)
