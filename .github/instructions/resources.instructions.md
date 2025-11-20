# Resources-Specific Instructions for Sigwin Infra

## Purpose

The `resources/` directory contains reusable Makefile includes that provide standardized build, test, and deployment targets for various project types. These resources are consumed by external projects through package managers (Composer, npm, pip).

## Resource Structure

### Directory Organization

Each subdirectory in `resources/` represents a specific technology or functionality:

- **Common/**: Base functionality, platform detection, core utilities
- **PHP/**: PHP library and application support
- **Symfony/**: Symfony-specific extensions
- **Pimcore/**: Pimcore-specific extensions
- **Node/**: Node.js tooling
- **Compose/**: Docker Compose orchestration
- **Secrets/**: Secret file management
- **Monorepo/**: Multi-package repository support
- **YASSG/**: Static site generation
- **Gitlab/**: GitLab CI/CD integration
- **Lighthouse/**: Lighthouse testing
- **Visual/**: Visual regression testing

### File Types in Resources

1. **`.mk` files**: Makefile includes that define targets and functions
2. **`.dist` files**: Template configuration files copied during `make init`
3. **Other templates**: Various template files consumed by projects

## Makefile Include Best Practices

### Required Header Pattern

Every `.mk` file must start with:

```makefile
ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
```

This ensures resources can locate themselves regardless of installation location (vendor, node_modules, or monorepo).

### Include Chain Pattern

Follow proper inheritance when including parent resources:

```makefile
# Example: Symfony/common.mk
ifndef SIGWIN_INFRA_ROOT
SIGWIN_INFRA_ROOT := $(dir $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))))
endif
include ${SIGWIN_INFRA_ROOT:%/=%}/PHP/common.mk

# Symfony-specific targets here...
```

### Defining Targets

All targets should include help documentation:

```makefile
target-name: dependencies ## Description of what this target does
	$(call block_start,Target Name)
	@echo "Commands here..."
	$(call block_end)
```

Key points:
- Use `##` comment after target for help text
- Wrap execution in `block_start` and `block_end` for GitHub Actions grouping
- Use `.SILENT:` at file start to suppress Make output noise
- Prefix echoes with `@` to hide the echo command itself

### Variable Naming

- Use `UPPERCASE_WITH_UNDERSCORES` for user-configurable variables
- Prefix internal variables with `_` if needed
- Document expected environment variables
- Provide sensible defaults

### Platform Detection

Use `OS_FAMILY` variable for platform-specific behavior:

```makefile
# OS_FAMILY will be: Linux, Darwin, or Windows
# Include platform-specific configuration
include ${SIGWIN_INFRA_ROOT:%/=%}/Common/Platform/${OS_FAMILY}/default.mk
```

### Docker Command Pattern

When running commands in Docker:

```makefile
DOCKER_RUN_PHP := docker run --rm \
	-v $(CURDIR):/project \
	-v $(HOME)/.composer:/tmp/.composer \
	-u $(shell id -u):$(shell id -g) \
	$(PHPQA_DOCKER_IMAGE)

target:
	$(DOCKER_RUN_PHP) command-here
```

Key aspects:
- Mount project directory to `/project`
- Mount home caches for faster execution
- Run as host user to avoid permission issues
- Use variables for image names to allow customization

## Resource Dependencies

### Inheritance Hierarchy

Understand the resource inheritance to avoid duplication:

```
Common/default.mk
├── PHP/common.mk
│   ├── PHP/library.mk
│   ├── PHP/monorepo-library.mk
│   ├── PHP/phar.mk
│   ├── Symfony/common.mk
│   │   └── Symfony/application.mk
│   └── Pimcore/common.mk
│       ├── Pimcore/library.mk
│       └── Pimcore/application.mk
├── Node/common.mk
└── Compose/common.mk
```

### Extending Resources

When extending a resource:

1. Include the parent resource first
2. Add or override targets as needed
3. Don't duplicate targets from parent
4. Document what's being extended and why

## Function Definitions

### Block Functions

Use for GitHub Actions log grouping:

```makefile
block_start:
	@$(if $(GITHUB_ACTIONS),echo "::group::$(1)",echo "==> $(1)")

block_end:
	@$(if $(GITHUB_ACTIONS),echo "::endgroup::")
```

### File Prefix Function

Support version-specific config files:

```makefile
# Looks for files like 8.4-phpstan.neon.dist, falls back to phpstan.neon.dist
file_prefix = $(shell \
	if [ -f "$(1)-$(2)" ]; then \
		echo "$(1)-"; \
	fi \
)
```

### Reusable Patterns

When you see repeated patterns, extract to functions:

```makefile
define run_in_docker
	docker run --rm -v $(CURDIR):/project $(1) sh -c "$(2)"
endef

# Usage:
target:
	$(call run_in_docker,image-name,command)
```

## Distribution Files

### .dist File Convention

Template files should have `.dist` extension and be copied during `make init`:

- `composer.json.dist` → `composer.json`
- `.env.dist` → `.env`
- `phpstan.neon.dist` → `phpstan.neon`

### Init Target Pattern

Resources can define files to copy during initialization:

```makefile
init: parent-init-target ## Initialize resource-specific files
	@$(call copy_if_missing,source.dist,destination)
```

## Common Targets to Provide

### Standard Target Names

Use consistent naming across resources:

- `dist` - Prepare codebase for commit (run all checks)
- `analyze` - Run all static analysis
- `test` - Run all tests
- `build/dev` - Build for development
- `build/prod` - Build for production
- `start/dev` - Start development server
- `start/prod` - Start production server
- `stop` - Stop servers
- `clean` - Clean temporary files
- `sh/{tool}` - Open shell in tool container

### Help Target

Always provide or extend the help target:

```makefile
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
```

## Environment Variables

### Standard Variables

Document expected environment variables:

- `PHPQA_DOCKER_IMAGE` - PHP QA tools Docker image
- `NODE_DOCKER_IMAGE` - Node.js Docker image  
- `GITHUB_ACTIONS` - Detect GitHub Actions environment
- `OS_FAMILY` - Platform detection (Linux/Darwin/Windows)
- `APP_ENV` - Application environment (dev/test/prod)

### Variable Documentation

Add comments explaining variables:

```makefile
# Docker image for PHP quality assurance tools
# Override with: make PHPQA_DOCKER_IMAGE=custom/image target
PHPQA_DOCKER_IMAGE ?= jakzal/phpqa:php8.4-alpine
```

## Modifying Existing Resources

### Minimal Changes

When modifying resources:

1. Understand which projects consume this resource
2. Maintain backward compatibility when possible
3. Test changes with multiple project types
4. Update documentation if behavior changes
5. Add tests for new functionality

### Breaking Changes

If breaking changes are necessary:

1. Document in changelog/PR description
2. Provide migration guide
3. Update version in package.json
4. Update consuming project examples

### Testing Changes

Before committing resource changes:

```bash
make test/phpunit          # Run functional tests
make analyze              # Run static analysis
make dist                 # Run full validation
```

## Security Considerations

### Secrets in Resources

- Never hardcode secrets in resource files
- Always use environment variables or `.secret` files
- Add `.secret` to `.gitignore` patterns
- Provide `.secret.dist` templates

### Command Injection

- Properly quote variables in shell commands
- Validate user input where applicable
- Use Make's built-in escaping

### Docker Security

- Use official images from trusted sources
- Pin image versions (avoid `latest`)
- Run containers as non-root when possible
- Mount only necessary directories

## Documentation

### Inline Comments

Add comments for complex logic:

```makefile
# This target needs to run in Docker because it requires specific PHP extensions
# that may not be available on the host system
target:
	$(DOCKER_RUN_PHP) command
```

### README Updates

When adding or significantly changing resources:

1. Update main README.md with new commands
2. Add examples of usage
3. Document configuration options
4. Update architecture section if needed

## Version Compatibility

Resources must support:

- PHP 8.1, 8.2, 8.3, 8.4
- Symfony 6.4+, 7.0+
- Node.js LTS versions (18, 20, 22)
- Docker Compose v2

Use version-specific files when needed:

- `8.4-phpstan.neon.dist` for PHP 8.4-specific configuration
- Check PHP version in Makefile when necessary:

```makefile
PHP_VERSION := $(shell php -r "echo PHP_VERSION_ID;")
```

## Common Pitfalls to Avoid

1. **Don't use absolute paths** - Resources should work from any location
2. **Don't assume tools are installed** - Use Docker for all tooling
3. **Don't break the include chain** - Always include parent resources first
4. **Don't ignore platform differences** - Test on Linux, macOS, Windows
5. **Don't pollute global namespace** - Prefix internal targets with `_` if needed
6. **Don't forget help documentation** - Every user-facing target needs `##` comment
7. **Don't skip testing** - Add functional tests for new resources
