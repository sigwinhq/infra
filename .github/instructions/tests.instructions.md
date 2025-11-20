# Test-Specific Instructions for Sigwin Infra

## Test Structure

All tests in this repository are **functional tests** that validate Makefile resources, not unit tests for application code.

### Test Base Class

All tests extend `Sigwin\Infra\Test\Functional\MakefileTestCase` which provides:

- Temporary project directory creation and cleanup
- Makefile execution utilities
- Helper methods for validating Make command output
- Standardized test patterns for resource validation

**Important**: The test class name must directly reflect which Makefile resource it is testing. For example:
- `LibraryTest` tests `resources/PHP/library.mk`
- `ApplicationTest` tests `resources/Symfony/application.mk`
- `DefaultTest` tests `resources/Common/default.mk`

### Required Test Attributes

Every test class must have these PHPUnit attributes:

```php
#[\PHPUnit\Framework\Attributes\CoversNothing]  // Tests integration, not specific code
#[\PHPUnit\Framework\Attributes\Medium]         // Tests run external Make commands
```

Use `#[\Override]` attribute for methods that override parent methods.

### Required Abstract Methods

When creating a new test class extending `MakefileTestCase`, implement:

1. **`getExpectedInitPaths()`**: Returns array of resource paths that should be initialized
   ```php
   protected function getExpectedInitPaths(): array
   {
       return [
           'PHP/common',
           'PHP/library',
       ];
   }
   ```

2. **`getExpectedHelpCommandsExecutionPath()`**: Returns array mapping Make targets to expected command paths
   ```php
   protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
   {
       return [
           'help' => [
               self::generateHelpExecutionPath(),
           ],
           'dist' => [
               self::generateHelpExecutionPath('composer/normalize'),
               self::generateHelpExecutionPath('cs'),
               self::generateHelpExecutionPath('analyze'),
               self::generateHelpExecutionPath('test'),
           ],
       ];
   }
   ```

## Writing Test Cases

### Test Naming

- Class name: Must match the Makefile resource being tested (e.g., `LibraryTest` for `library.mk`, `ApplicationTest` for `application.mk`)
- Method name: `test{Behavior}` (e.g., `testMakefileExists`, `testMakefileHasHelp`)
- Use descriptive names that explain what is being validated

### Test Organization

Organize tests by resource type:
- `tests/functional/Common/` - Common resource tests
- `tests/functional/PHP/` - PHP resource tests
- `tests/functional/Symfony/` - Symfony resource tests
- `tests/functional/Pimcore/` - Pimcore resource tests
- `tests/functional/Node/` - Node.js resource tests
- `tests/functional/YASSG/` - YASSG resource tests

### Standard Test Pattern

1. Create temporary project structure
2. Copy relevant resource files
3. Execute Make command
4. Assert expected output or behavior
5. Cleanup handled automatically by parent class

### Helper Methods

The `MakefileTestCase` base class provides helper methods (refer to the class for complete list):

- `self::getRoot()` - Get root directory of temporary test project
- `self::getMakefilePath()` - Get path to Makefile being tested
- `generateHelpExecutionPath()` - Generate expected path for help commands
- Methods for executing and validating Make commands

### File Header

All test files must include the standard file header:

```php
<?php

declare(strict_types=1);

/*
 * This file is part of the Sigwin Infra project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
```

## Test Execution

### Running Tests

```bash
make test/phpunit          # Run PHPUnit tests
```

### Test Requirements

- Tests must pass PHPUnit strict settings
- Tests must be deterministic (no random failures)
- Tests run on Linux, macOS, and Windows in CI - all three platforms must pass for successful PR
- Test logic must be platform-agnostic to ensure cross-platform compatibility

## Common Test Scenarios

### Testing Make Target Availability

Verify that expected Make targets are available and documented:

```php
public function testMakefileHasHelp(): void
{
    $output = $this->executeMakeCommand('help');
    self::assertStringContainsString('dist', $output);
    self::assertStringContainsString('Prepare the codebase for commit', $output);
}
```

### Testing Resource Initialization

Verify that resources are properly initialized in consuming projects:

```php
public function testInitializesResources(): void
{
    $this->executeMakeCommand('init');
    
    foreach ($this->getExpectedInitPaths() as $path) {
        self::assertFileExists(
            self::getRoot() . '/' . str_replace('$PLATFORM', $this->getPlatform(), $path)
        );
    }
}
```

### Testing Command Execution Path

Verify that compound Make targets execute expected sub-targets in correct order:

```php
public function testDistExecutesExpectedCommands(): void
{
    $expectedPath = self::getExpectedHelpCommandsExecutionPath()['dist'];
    
    $output = $this->executeMakeCommand('dist --dry-run');
    // Assert expected commands are in output
}
```

## What NOT to Test

- Do not test Docker image internals
- Do not test external tools (PHPStan, Psalm, etc.) behavior
- Focus on testing the Makefile resource integration and command orchestration
- Tests must be platform-agnostic and avoid hardcoding platform-specific paths or behaviors

## Adding Tests for New Resources

When adding a new Makefile resource:

1. Create corresponding test class in `tests/functional/{ResourceType}/` with name matching the `.mk` file
2. Extend `MakefileTestCase`
3. Implement required abstract methods
4. Add tests for:
   - Makefile existence
   - Help documentation
   - Resource initialization
   - Target execution paths
   - Integration with parent resources
5. Ensure tests pass on Linux, macOS, and Windows

## Debugging Tests

When tests fail:

1. Check the temporary project directory path (output in test failures)
2. Manually run Make commands in that directory to debug
3. Use `--verbose` flag with Make commands for detailed output
4. Check that resource files are copied correctly
5. Verify environment variables are set as expected

## Test Isolation

- Each test gets a fresh temporary directory
- No state shared between tests
- Cleanup handled automatically
- Tests can run in random order (enforced by PHPUnit config)
