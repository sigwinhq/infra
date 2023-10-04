SHELL := pwsh.exe

COMMA := ,
EMPTY :=
SPACE := $(empty) $(empty)

# TODO: rewrite output with https://learn.microsoft.com/en-us/powershell/module/microsoft.powershell.core/about/about_special_characters?view=powershell-7.3#escape-e
help: ## Prints this help
	@Select-String -Pattern '^ *(?<name>[-a-zA-Z0-9_/]+) *:.*## *(?<help>.+)' $(subst $(SPACE),${COMMA},$(strip ${MAKEFILE_LIST})) | Sort-Object {$$_.Matches[0].Groups["name"]} | ForEach-Object{"{0, -20}" -f $$_.Matches[0].Groups["name"] | Write-Host -NoNewline -BackgroundColor Magenta -ForegroundColor White; " {0}" -f $$_.Matches[0].Groups["help"] | Write-Host -ForegroundColor White}

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
