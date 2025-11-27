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

The `make help` command can be customized with project-specific information by adding metadata to your `package.json` (Node.js projects) or `composer.json` (PHP projects):

**For package.json:**
```json
{
  "name": "my-project",
  "description": "A brief description of your project",
  "homepage": "https://docs.example.com",
  "repository": {
    "type": "git",
    "url": "https://github.com/example/my-project.git"
  },
  "extra": {
    "sigwin/infra": {
      "help_color": "46",
      "local_urls": [
        {"url": "http://localhost:3000", "description": "Main dev server"},
        {"url": "http://api.local.test", "description": "API endpoint"}
      ]
    }
  }
}
```

**For composer.json:**
```json
{
  "name": "my-project/library",
  "description": "A brief description of your project",
  "homepage": "https://docs.example.com",
  "support": {
    "source": "https://github.com/example/my-project"
  },
  "extra": {
    "sigwin/infra": {
      "help_color": "46",
      "local_urls": [
        {"url": "http://localhost:8000"},
        {"url": "http://api.local.test", "description": "Use when testing Google OAuth"}
      ]
    }
  }
}
```

**Alternative: Define in Makefile**

If you don't have or don't want to use package.json/composer.json, you can define these variables directly in your root Makefile:

```makefile
.SILENT:
export PROJECT_NAME := My Project
export PROJECT_DESCRIPTION := A brief description of your project
export PROJECT_HOMEPAGE := https://docs.example.com
export PROJECT_REPOSITORY := https://github.com/example/my-project
export SIGWIN_INFRA_HELP_COLOR := 46
export PROJECT_LOCAL_URLS := http://localhost:3000|Main dev server,http://localhost:3001|HMR server

include vendor/sigwin/infra/resources/PHP/library.mk
```

**Note**: The `PROJECT_LOCAL_URLS` variable should be a comma-separated list of URLs. You can optionally add descriptions by separating the URL and description with a pipe (`|`) character, e.g., `http://localhost:3000|Description`.

When configured, `make help` will display a header with:
- **Project name** - from `name` field or `PROJECT_NAME` (displayed with custom background color)
- **Description** - from `description` field or `PROJECT_DESCRIPTION`
- **Local URLs** - from `extra."sigwin/infra".local_urls` field or `PROJECT_LOCAL_URLS` (array/list of URLs with optional descriptions)
- **Homepage** - from `homepage` field or `PROJECT_HOMEPAGE`
- **Repository** - from `repository.url` (package.json) or `support.source` (composer.json) or `PROJECT_REPOSITORY`

Custom colors can be set using ANSI color codes in `extra."sigwin/infra".help_color` or `SIGWIN_INFRA_HELP_COLOR` (default is `45` for magenta). Common options:
- `44` - Blue
- `45` - Magenta (default)
- `46` - Cyan
- `41` - Red
- `42` - Green
- `43` - Yellow

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

### Node Application Projects
```bash
make dist                    # Run all checks (analyze, test)
make analyze                 # Run all analysis tools (lint, type-check)
make analyze/lint            # Run linter
make analyze/type-check      # Run type checking
make test                    # Run tests (unit + functional)
make test/vitest             # Run Vitest unit tests
make test/e2e                # Run end-to-end tests
make build/dev               # Build Docker images for dev environment
make build/prod              # Build Docker images for prod environment
make start/dev               # Start application in dev mode
make start/test              # Start application in test mode
make start/prod              # Start application in prod mode
make stop                    # Stop running application
make sh/app                  # Open shell in application container
make sh/node                 # Open Node shell in Docker container
make setup/test              # Setup test environment
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

### Running Tests

**Always use the direct PHPUnit command for fastest feedback:**
```bash
vendor/bin/phpunit                    # Run all tests directly (recommended)
vendor/bin/phpunit --filter TestName  # Run specific test
```

**Only use `make test/phpunit` when:**
- Testing the full Docker-based CI pipeline
- You need to verify Docker environment compatibility
- The README specifically asks you to test the Docker setup

**Why?** The Docker image may be missing tools (like `jq`) that are available on the host, causing false failures.

### Test-First Approach

**Critical**: Run tests IMMEDIATELY after making changes. Do not:
- Make multiple changes before testing
- Try to predict if changes will work
- Debug "in your head" instead of using the test suite
- Spend time analyzing why something might fail - just test it

The test suite is your fastest feedback loop. Use it.

### Debugging Workflow

When tests fail:

1. **Run tests directly first**: `vendor/bin/phpunit` (not via Make/Docker)
2. **Check the actual failure message** - don't assume what's wrong
3. **Make one small change** and re-test immediately
4. **If stuck after 2-3 attempts**, ask the user for guidance rather than going deeper
5. **Trust the test suite** - if tests pass, the solution is correct

**Anti-patterns to avoid:**
- ❌ Debugging test infrastructure when tests fail
- ❌ Writing debug scripts to trace test execution
- ❌ Assuming environment issues without evidence
- ❌ Making multiple changes before re-testing

## Development Workflow

### Problem-Solving Approach

When making changes to this repository:

- **Start with the simplest possible solution** - If it feels complicated, step back and reconsider
- **Test immediately** - Change → Test → Iterate (tight feedback loop)
- **Don't debug hypothetical problems** - Only fix actual failures
- **Trust the test suite** - If tests pass, the solution is correct
- **If you're writing debug/trace code, you've gone too far** - Step back and simplify

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
