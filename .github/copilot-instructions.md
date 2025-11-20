# Copilot Instructions for Sigwin Infra

## Project Overview

Sigwin Infra is a reusable infrastructure framework providing standardized Makefiles for different project types (PHP libraries, Symfony applications, Pimcore applications, Node.js projects, etc.). Projects consume these resources by including the appropriate `.mk` files in their Makefiles.

**Key Characteristics:**
- Multi-language infrastructure framework (PHP, Node.js, Python)
- Docker-based tooling for consistent environments
- Modular Makefile system with inheritance
- Resource-based architecture in `resources/` directory
- Distributed via multiple package managers (Composer, npm, pip/poetry/uv)

## Architecture and Structure

### Resource Organization

The `resources/` directory contains modular Makefile includes organized by technology:

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

Resources follow an inheritance model where higher-level resources include lower-level ones:
- `PHP/library.mk` includes `PHP/common.mk`
- `Symfony/common.mk` includes `PHP/common.mk`
- `Symfony/application.mk` includes `Symfony/common.mk` + `Compose/common.mk`
- All resources include `Common/default.mk` for base functionality

Each resource defines `SIGWIN_INFRA_ROOT` to locate the resource directory, enabling consumption from vendor or monorepo locations.

### Docker-Based Tooling

All PHP and Node.js commands run inside Docker containers:
- PHP: Uses `jakzal/phpqa` image (configurable via `PHPQA_DOCKER_IMAGE`)
- Node: Uses official `node:alpine` image
- Containers mount the project directory, home directory caches, and execute with host user permissions

## Coding Standards and Conventions

### Makefile Best Practices

When modifying or creating Makefile resources:

1. **Silence output by default**: Use `.SILENT:` at the top of Makefiles
2. **Define SIGWIN_INFRA_ROOT**: Always set this variable to locate resources
3. **Include dependencies**: Use the proper include chain for resource inheritance
4. **Add help comments**: Use `## Description` after target declarations for auto-generated help
5. **Use block functions**: Wrap output with `block_start` and `block_end` for GitHub Actions integration
6. **Platform detection**: Use `OS_FAMILY` variable for platform-specific behavior
7. **Docker consistency**: Run tools in Docker containers, not directly on host

### Code Style

- **Makefile syntax**: Follow GNU Make conventions with proper indentation (tabs, not spaces)
- **Shell commands**: Use POSIX-compliant shell syntax where possible
- **Variable naming**: Use UPPERCASE for Makefile variables
- **Function naming**: Use lowercase with underscores for custom functions

### PHP Development

When working with PHP code in this repository:

- **No source code**: This repository contains only infrastructure code (Makefiles, configuration), not PHP application code
- **Test code only**: PHP code exists only in `tests/functional/` for testing the Makefile resources
- **Follow PSR-12**: Use PHP-CS-Fixer with the configuration in `resources/PHP/php-cs-fixer.php`
- **Type strictness**: Always use `declare(strict_types=1);`
- **Static analysis**: Code must pass PHPStan (level max) and Psalm (strict mode)

## Testing Requirements

### Test Structure

- Tests are located in `tests/functional/`
- Each test extends `MakefileTestCase` which provides utilities for testing Makefile resources
- Tests create temporary project directories, copy resource files, execute Make commands, and validate behavior

### Running Tests

```bash
make test/phpunit          # Run PHPUnit tests only
make test/infection        # Run mutation testing (requires prior coverage)
make test                  # Run all tests with mutation testing
```

### Test Coverage

- PHPUnit requires coverage metadata with `requireCoverageMetadata="true"`
- Infection mutation testing enforces 100% MSI (Mutation Score Indicator)
- Tests must be thorough and cover edge cases

### Writing Tests

When adding or modifying tests:

1. Extend `Sigwin\Infra\Test\Functional\MakefileTestCase`
2. Use the helper methods to create temporary project structures
3. Test actual Make command execution, not just code logic
4. Validate expected output and behavior
5. Clean up temporary resources in tearDown

## Build and Validation Process

### Pre-commit Checks

Before committing changes, always run:

```bash
make dist                  # Runs: normalize, cs, analyze, test
```

This executes:
1. `composer/normalize` - Normalize composer.json format
2. `cs` - Fix code style issues automatically
3. `analyze` - Run all static analysis tools
4. `test` - Run all tests with mutation testing

### Static Analysis

The project uses multiple analysis tools:

- **PHPStan**: Level max with strict and deprecation rules
- **Psalm**: Strict mode with PHP version enforcement
- **PHP-CS-Fixer**: Configured via `resources/PHP/php-cs-fixer.php`
- **Composer Validate**: Check composer.json validity and security

Run individually:
```bash
make analyze/composer      # Validate composer.json
make analyze/cs           # Check code style (dry-run)
make analyze/phpstan      # Run PHPStan
make analyze/psalm        # Run Psalm
```

### Docker Environment

When testing Docker-based functionality:

- Use `make sh/php` to open a PHP shell in the Docker container
- Verify that host user permissions are maintained in containers
- Test on different platforms (Linux, macOS, Windows) when possible
- Ensure volume mounts work correctly for cache directories

## Security Guidelines

### Secrets Management

- Never commit secrets, API keys, or credentials to the repository
- Use `.dist` files for configuration templates
- The `Secrets/` resources provide patterns for secret file management
- Always use `.gitignore` to exclude sensitive files

### Docker Security

- Use official Docker images from trusted sources
- Pin image versions when possible (avoid `latest` tags in production)
- Run containers with least privilege (non-root user when possible)
- Mount only necessary directories into containers

### Dependency Management

- Keep dependencies up to date via Dependabot (configured in `.github/dependabot.yml`)
- Review dependency updates for security implications
- Use `composer audit` and similar tools to check for vulnerabilities

## Pull Request Guidelines

### PR Requirements

When creating or reviewing PRs:

1. **Reference the issue**: Link to related GitHub issues
2. **Minimal changes**: Make surgical, focused changes
3. **Test coverage**: Ensure tests cover new functionality
4. **Documentation**: Update README.md and relevant docs
5. **Pass all checks**: All CI checks must pass before merge

### Commit Messages

- Use clear, descriptive commit messages
- Follow conventional commit format when applicable
- Reference issue numbers with `#123` syntax

### Code Review

- PRs require review before merging
- Address all review comments
- Rerun `make dist` after making changes
- Verify CI passes after addressing feedback

## Common Workflows

### Adding a New Resource

1. Create the new `.mk` file in appropriate `resources/` subdirectory
2. Define `SIGWIN_INFRA_ROOT` at the top
3. Include necessary parent resources
4. Define targets with help comments
5. Add functional tests in `tests/functional/`
6. Update README.md with new commands
7. Run `make dist` to validate

### Modifying Existing Resources

1. Understand the resource inheritance chain
2. Make minimal changes to achieve the goal
3. Test with multiple project types that use the resource
4. Verify backward compatibility
5. Update tests to cover new behavior
6. Run `make dist` to validate

### Debugging Make Targets

- Use `make -n <target>` to see commands without executing
- Add `@echo` statements to debug variable values
- Test in isolated temporary directories
- Use `make sh/php` to debug inside Docker containers

## GitHub Actions Integration

The `block_start` and `block_end` functions automatically wrap command output in GitHub Actions groups when `GITHUB_ACTIONS` environment variable is set. This provides better log organization in CI.

Example in Makefile:
```makefile
my-target:
	$(call block_start,My Target)
	@echo "Executing commands..."
	$(call block_end)
```

## Quality Standards

This project maintains high quality standards:

- **PHPStan**: Level max with strict rules
- **Psalm**: Strict mode enabled
- **Infection**: 100% MSI requirement
- **Code Style**: Automated via PHP-CS-Fixer
- **Test Coverage**: Required for all code paths
- **Mutation Testing**: All mutants must be killed

These standards are non-negotiable for merged code.

## Additional Context

### No Source Code Directory

This repository does NOT have a `src/` directory. The `infection.json.dist` configuration references `src` but this is a template for consuming projects. Tests are the only PHP code in this repository.

### Platform Compatibility

Makefiles must work across Linux, macOS (Darwin), and Windows (via WSL or Git Bash). Use platform-specific includes in `Common/Platform/` when needed.

### Version Support

The project supports:
- PHP 8.1, 8.2, 8.3, 8.4
- Multiple Symfony versions (6.4+, 7.0+)
- Node.js LTS versions
- Python 3.9+

Use version-specific configuration files when needed (e.g., `8.4-phpstan.neon.dist`).
