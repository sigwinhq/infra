# Sigwin Infra

Sigwin's default per-project infra.

## Project Overview

Sigwin Infra is a reusable infrastructure framework that provides standardized Makefiles for different project types (PHP libraries, Symfony applications, Pimcore applications, Node.js projects, etc.). Projects consume these resources by including the appropriate `.mk` files in their Makefiles.

## Architecture

### Resource System

The `resources/` directory contains modular Makefile includes organized by technology and purpose:

- **Common**: Platform-agnostic and platform-specific (Linux/Darwin/Windows) base configuration
- **PHP**: PHP library, monorepo library, and PHAR building targets
- **Symfony**: Symfony application-specific targets (extends PHP)
- **Pimcore**: Pimcore application-specific targets (extends PHP)
- **Node**: Node.js tooling via Docker
- **Compose**: Docker Compose orchestration (build, start, stop, shell access)
- **Secrets**: Secret file management from `.dist` templates
- **Monorepo**: Multi-package repository support
- **YASSG**: Static site generation support
- **Gitlab/Lighthouse/Visual**: CI/CD and testing integrations

### Inclusion Pattern

Resources follow an inheritance model:
- `PHP/library.mk` includes `PHP/common.mk`
- `Symfony/common.mk` includes `PHP/common.mk`
- `Symfony/application.mk` includes `Symfony/common.mk` + `Compose/common.mk`
- All resources include `Common/default.mk` for base functionality

Each resource defines `SIGWIN_INFRA_ROOT` to locate the resource directory, enabling consumption from vendor or monorepo locations.

### Docker-Based Tooling

All PHP and Node.js commands run inside Docker containers to ensure consistent environments:
- PHP: Uses `jakzal/phpqa` image (configurable via `PHPQA_DOCKER_IMAGE`)
- Node: Uses official `node:alpine` image
- Containers mount the project directory, home directory caches, and execute with host user permissions

### Init System

The `init` target (in `Common/default.mk`) copies distribution files from resource directories to consuming projects. Each resource directory can contain template files that get copied during initialization.

## Common Commands

### Help
```bash
make help                    # Show all available targets
```

### PHP Library Projects
```bash
make dist                    # Run all checks (normalize, cs, analyze, test)
make analyze                 # Run all analysis tools (composer, cs, phpstan, psalm)
make cs                      # Fix code style issues
make analyze/cs              # Check code style (dry-run)
make analyze/phpstan         # Run PHPStan static analysis
make analyze/psalm           # Run Psalm static analysis
make test                    # Run tests with mutation testing
make test/phpunit            # Run PHPUnit tests only
make test/phpunit-coverage   # Run PHPUnit with coverage
make test/infection          # Run Infection mutation testing (requires coverage)
make sh/php                  # Open PHP shell in Docker container
make composer/install        # Install dependencies
make composer/normalize      # Normalize composer.json
```

### Symfony/Pimcore Application Projects
All PHP library commands plus:
```bash
make build/dev               # Build Docker images for dev environment
make build/prod              # Build Docker images for prod environment
make start/dev               # Start application in dev mode
make start/test              # Start application in test mode
make start/prod              # Start application in prod mode
make stop                    # Stop running application
make sh/app                  # Open shell in application container
make test/behat              # Run Behat functional tests
make setup/test              # Setup test database and environment
make setup/filesystem        # Create and configure var directories
make clean                   # Clear logs and caches
```

### Environment Configuration

Docker Compose projects automatically copy `.env.dist` to `.env` if `.env` doesn't exist (see `Compose/common.mk`).

Secrets management: `.infra/secrets/*.secret.dist` files are copied to `*.secret` via the `secrets` target.

## Testing

Tests are located in `tests/functional/` and validate that the Makefile resources work correctly. Each test extends `MakefileTestCase` which:
- Creates temporary project directories
- Copies resource files
- Executes Make commands
- Validates expected behavior

Run tests with `make test/phpunit` (which runs PHPUnit).

## Development Workflow

### Installing as a Dependency

Sigwin Infra is designed to be installed using your stack's native dependency management tool:

- **PHP/Composer**: `composer require --dev sigwin/infra`
- **Node/npm**: `npm install --save-dev @sigwinhq/infra`
- **Python (Pip/Poetry/UV)**: `pip install sigwin-infra` (or `poetry add --group dev sigwin-infra`, `uv add --dev sigwin-infra`)

The package provides the same Makefile resources regardless of installation method. More stacks may be added in the future following the same pattern.

### Integration Steps

1. **Bootstrap**: Use `resources/PHP/library/Makefile` pattern to download infra on first run (PHP projects)
2. **Including resources**: Add `include vendor/sigwin/infra/resources/<Type>/<file>.mk` to project Makefile (or `node_modules/@sigwinhq/infra/resources/...` for npm)
3. **Version-specific configs**: Use `file_prefix` function to support PHP version-specific config files (e.g., `8.4-phpstan.neon.dist`)
4. **Platform detection**: `OS_FAMILY` automatically detects Linux/Darwin/Windows and includes platform-specific configuration

## Quality Standards

- PHPStan: Level max with strict and deprecation rules
- Psalm: Strict mode with PHP version enforcement
- PHP-CS-Fixer: Configured via `resources/PHP/php-cs-fixer.php`
- Infection: 100% MSI requirement with sensible mutator configuration

## GitHub Actions Integration

The `block_start` and `block_end` functions automatically wrap command output in GitHub Actions groups when `GITHUB_ACTIONS` environment variable is set.
