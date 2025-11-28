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

namespace Sigwin\Infra\Test\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
abstract class MakefileTestCase extends TestCase
{
    /**
     * @var array<string, string>
     */
    private array $help = [
        'analyze' => 'Analyze the codebase',
        'analyze/lighthouse' => 'Analyze built files using Lighthouse',
        'build' => 'Build app for "APP_ENV" target (defaults to "prod")',
        'build/dev' => 'Build app for "dev" target',
        'build/prod' => 'Build app for "prod" target',
        'certificates' => 'Generate mkcert certificates for local URLs',
        'clean' => 'Clear logs and system cache',
        'dist' => 'Prepare the codebase for commit',
        'help' => 'Prints this help',
        'setup/filesystem' => 'Setup: filesystem (var, public/var folders)',
        'setup/test' => 'Setup: create a functional test runtime',
        'sh/app' => 'Run application shell',
        'sh/node' => 'Run Node shell',
        'sh/php' => 'Run PHP shell',
        'start' => 'Start app in APP_ENV mode (defined in .env)',
        'start/dev' => 'Start app in "dev" mode',
        'start/prod' => 'Start app in "prod" mode',
        'start/test' => 'Start app in "test" mode',
        'stop' => 'Stop app',
        'test' => 'Test the codebase',
        'test/functional' => 'Test the codebase, functional tests',
        'test/unit' => 'Test the codebase, unit tests',
        'visual/reference' => 'Generate visual testing references',
    ];

    /**
     * @var array<non-empty-string, string>
     */
    private static array $lineMatches = [
        '/^PROJECT_NAME=/' => '$(call read_project_metadata)',
        '/^\$metadataFile =/' => '$(call read_project_metadata)',
    ];

    /**
     * @var array<string, string>
     */
    protected array $helpOverride = [];

    /**
     * @param null|array<string, string> $env
     *
     * @return array<string, list<string>>
     */
    abstract protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array;

    /**
     * @return list<string>
     */
    abstract protected function getExpectedInitPaths(): array;

    public function testMakefileExists(): void
    {
        self::assertFileExists(
            self::getRoot().\DIRECTORY_SEPARATOR.self::getMakefilePath()
        );
    }

    public function testMakefileHasHelp(): void
    {
        $actual = self::getMakefileHelp();
        $expected = $this->getExpectedHelp();

        if (\PHP_OS_FAMILY === 'Windows') {
            $expected = preg_replace('/\r\n|\r|\n/', "\n", self::stripColoring($expected));
        }

        self::assertSame($expected, $actual);
    }

    public function testHelpIsTheDefaultCommand(): void
    {
        $expected = self::dryRun('help');
        $actual = self::dryRun();

        self::assertSame($expected, $actual);
    }

    public function testMakefileHasInit(): void
    {
        // Match the form produced by `make --dry-run` on some platforms where the
        // `cp -a` source is not expressed as `dir/. .` but as `dir .`.
        $expected = array_map(static fn (string $path): string => \sprintf('if [ -d "$ROOT/resources/%1$s" ]; then cp -a $ROOT/resources/%1$s .; fi', $path), $this->getExpectedInitPaths());
        $expected = array_merge(...array_map(static fn ($value) => [$value, 'if [ -f .gitattributes.dist ]; then mv .gitattributes.dist .gitattributes; fi'], $expected));
        $actual = self::dryRun('init');

        self::assertSame($expected, $actual);
    }

    /**
     * @param list<string>          $expected
     * @param array<string, string> $env
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideMakefileCommandsWorkCases')]
    public function testMakefileCommandsWork(string $command, array $expected, array $env): void
    {
        $actual = self::dryRun($command, env: $env);
        foreach ($actual as $idx => $line) {
            foreach (self::$lineMatches as $matcher => $replacement) {
                if (preg_match($matcher, $line) === 1) {
                    $actual[$idx] = $replacement;
                    continue 2;
                }
            }
        }
        if ($command === 'help') {
            self::assertNotEmpty($actual);

            return;
        }

        self::assertSame($expected, $actual, \sprintf('Makefile command "%1$s" did not produce expected execution path.', $command));
    }

    /**
     * @return iterable<array-key, array{string, list<string>, array<string, string>}>
     *
     * @psalm-suppress PossiblyUnusedMethod false positive
     */
    public static function provideMakefileCommandsWorkCases(): iterable
    {
        $commands = self::getMakefileHelpCommands();

        $envs = self::getEnvs();
        foreach ($envs as $env) {
            $expected = static::getExpectedHelpCommandsExecutionPath($env);
            $expected['help'] = [];
            foreach ($commands as $command) {
                self::assertArrayHasKey($command, $expected, \sprintf('No expected execution path defined for command "%1$s"', $command));
            }

            foreach ($expected as $command => $path) {
                yield [$command, $path, $env];
            }
        }
    }

    /**
     * @return iterable<array<string, string>>
     */
    protected static function getEnvs(): iterable
    {
        yield [];
    }

    protected function getExpectedHelp(): string
    {
        $commands = array_keys(static::getExpectedHelpCommandsExecutionPath([]));
        $commands[] = 'help';

        return $this->generateHelpHeader().$this->generateHelpList($commands);
    }

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArgument
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     */
    protected function generateHelpHeader(): string
    {
        // Check if package.json or composer.json exists in the entrypoint Makefile's directory
        $root = self::getRoot();
        $makefilePath = mb_ltrim(self::getMakefilePath(), '/\\');
        $entrypointDir = $root.\DIRECTORY_SEPARATOR.\dirname($makefilePath);

        $packageJson = $entrypointDir.\DIRECTORY_SEPARATOR.'package.json';
        $composerJson = $entrypointDir.\DIRECTORY_SEPARATOR.'composer.json';

        /**
         * @var null|array<string, mixed> $metadata
         */
        $metadata = null;
        if (file_exists($packageJson)) {
            $metadata = json_decode((string) file_get_contents($packageJson), true);
        } elseif (file_exists($composerJson)) {
            $metadata = json_decode((string) file_get_contents($composerJson), true);
        }

        if (! \is_array($metadata)) {
            return '';
        }

        $name = $metadata['name'] ?? null;
        $description = $metadata['description'] ?? null;

        if ($name === null && $description === null) {
            return '';
        }

        $header = "\n";

        $extra = \is_array($metadata['extra'] ?? null) ? $metadata['extra'] : [];
        $infra = \is_array($extra['sigwin/infra'] ?? null) ? $extra['sigwin/infra'] : [];
        $repository = \is_array($metadata['repository'] ?? null) ? $metadata['repository'] : [];
        $support = \is_array($metadata['support'] ?? null) ? $metadata['support'] : [];

        if ($name !== null) {
            /** @var string $color */
            $color = \is_string($infra['help_color'] ?? null) ? $infra['help_color'] : '45';
            /** @var string $name */
            $header .= \sprintf("\e[%sm%-78s\e[0m\n", $color, $name);
        }

        if ($description !== null) {
            /** @var string $description */
            $header .= \sprintf("\e[0;2m%s\e[0m\n", $description);
        }

        $localUrls = \is_array($infra['local_urls'] ?? null) ? $infra['local_urls'] : [];
        if (\count($localUrls) > 0) {
            $header .= "\e[0;2mLocal:\e[0m\n";
            foreach ($localUrls as $url) {
                if (\is_string($url)) {
                    $header .= \sprintf("  - %s\n", $url);
                } elseif (\is_array($url)) {
                    $urlStr = \is_string($url['url'] ?? null) ? $url['url'] : null;
                    $desc = \is_string($url['description'] ?? null) ? $url['description'] : null;
                    if ($urlStr !== null) {
                        if ($desc !== null) {
                            $header .= \sprintf("  - %s \e[0;2m(%s)\e[0m\n", $urlStr, $desc);
                        } else {
                            $header .= \sprintf("  - %s\n", $urlStr);
                        }
                    }
                }
            }
        }

        $homepage = \is_string($metadata['homepage'] ?? null) ? $metadata['homepage'] : null;
        $repo = \is_string($repository['url'] ?? null) ? $repository['url'] : (\is_string($support['source'] ?? null) ? $support['source'] : null);

        if ($homepage !== null && $repo !== null) {
            $header .= \sprintf("\e[0;2mHomepage:\e[0m   %s\n", $homepage);
        } elseif ($homepage !== null) {
            $header .= \sprintf("\e[0;2mHomepage:\e[0m %s\n", $homepage);
        }

        if ($repo !== null) {
            $header .= \sprintf("\e[0;2mRepository:\e[0m %s\n", $repo);
        }

        $header .= "\n";

        return $header;
    }

    /**
     * @param list<string> $commands
     */
    protected function generateHelpList(array $commands): string
    {
        $help = [];
        sort($commands);
        foreach ($commands as $command) {
            $help[] = \sprintf('%1$s[45m%2$s%1$s[0m %3$s', "\e", mb_str_pad($command, 20), $this->helpOverride[$command] ?? $this->help[$command] ?? '');
        }

        return implode("\n", $help)."\n";
    }

    /**
     * @param list<string> $dirs
     *
     * @return list<string>
     */
    protected static function generatePermissionsExecutionPath(array $dirs): array
    {
        $commands = [];
        foreach ($dirs as $dir) {
            $commands[] = \sprintf('mkdir -p %1$s', $dir);
            if (\PHP_OS_FAMILY === 'Linux') {
                $commands[] = \sprintf('setfacl -dRm          m:rwX  %1$s', $dir);
                $commands[] = \sprintf('setfacl -Rm           m:rwX  %1$s', $dir);
                $commands[] = \sprintf('setfacl -dRm u:`whoami`:rwX  %1$s', $dir);
                $commands[] = \sprintf('setfacl -Rm  u:`whoami`:rwX  %1$s', $dir);
                $commands[] = \sprintf('setfacl -dRm u:999:rwX %1$s', $dir);
                $commands[] = \sprintf('setfacl -Rm  u:999:rwX %1$s', $dir);
                $commands[] = \sprintf('setfacl -dRm u:root:rwX      %1$s', $dir);
                $commands[] = \sprintf('setfacl -Rm  u:root:rwX      %1$s', $dir);
            }
        }

        return $commands;
    }

    protected static function getMakefilePath(): string
    {
        if (\defined('static::MAKEFILE_PATH')) {
            /** @var string $path */
            $path = static::MAKEFILE_PATH;
            $path = realpath($path);
            if ($path === false) {
                throw new \LogicException('Failed to resolve MAKEFILE_PATH');
            }

            return str_replace(self::getRoot(), '', $path);
        }

        $path = str_replace([__NAMESPACE__.'\\', '\\'], ['', \DIRECTORY_SEPARATOR], static::class);
        $dir = pathinfo($path, \PATHINFO_DIRNAME);
        $name = pathinfo($path, \PATHINFO_FILENAME);
        if (! str_ends_with($name, 'Test')) {
            throw new \LogicException('Invalid test class name, expected to end with "Test"');
        }
        $name = mb_substr($name, 0, -4);

        return \sprintf('resources%2$s%1$s%2$s%3$s.mk', $dir, \DIRECTORY_SEPARATOR, mb_strtolower($name));
    }

    /**
     * @param list<string>                   $args
     * @param null|array<string, int|string> $env
     *
     * @return list<string>
     */
    protected static function dryRun(
        ?string $makeCommand = null,
        ?array $args = null,
        ?array $env = null,
        ?string $makefile = null,
        string $directory = __DIR__.'/../..',
    ): array {
        $args[] = '--dry-run';

        return array_values(array_filter(explode("\n", self::execute($makeCommand, $args, $env, $makefile, $directory)), static fn (string $line): bool => $line !== ''));
    }

    /**
     * @param list<string>                   $args
     * @param null|array<string, int|string> $env
     */
    protected static function execute(
        ?string $command = null,
        ?array $args = null,
        ?array $env = null,
        ?string $makefile = null,
        string $directory = __DIR__.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..',
    ): string {
        $makefile = str_replace('/', \DIRECTORY_SEPARATOR, $makefile ?? self::getMakefilePath());
        $fullCommand = ['make', '-f', self::getRoot().\DIRECTORY_SEPARATOR.mb_ltrim($makefile, '/\\')];
        if ($args !== null) {
            array_push($fullCommand, ...$args);
        }
        if ($command !== null) {
            $fullCommand[] = $command;
        }

        $directory = realpath($directory);
        if ($directory === false) {
            throw new \LogicException('Failed to get directory');
        }
        $process = new Process(
            $fullCommand,
            $directory,
            array_replace([
                'HOME' => '/home/user',
                'SIGWIN_INFRA_ROOT' => self::getRoot().\DIRECTORY_SEPARATOR.'resources',

                // streamline these to ensure consistent runtime environment
                'RUNNER' => '999',
                'APP_ENV' => 'env',
                'APP_ROOT' => self::getRoot(),
                'PHP_VERSION' => '',
                'GITHUB_ACTIONS' => '',
                'COMPOSE_PROJECT_NAME' => 'infra',
                'PIMCORE_KERNEL_CLASS' => 'App\Kernel',
            ], $env ?? []),
        );

        $filesystem = new Filesystem();
        $filesystem->remove(__DIR__.'/../../var/phpqa');

        $process->mustRun();
        $output = $process->getOutput();

        if (\PHP_OS_FAMILY === 'Windows') {
            /** @var string $output */
            $output = preg_replace('/\r\n|\r|\n/', "\n", $output);
        }

        return self::normalize($output);
    }

    private static function getMakefileHelp(): string
    {
        return self::execute('help');
    }

    /**
     * @return list<string>
     */
    private static function getMakefileHelpCommands(): array
    {
        $help = explode("\n", mb_trim(self::stripColoring(self::getMakefileHelp())));

        $commands = [];
        foreach ($help as $line) {
            // Skip empty lines
            $trimmedLine = mb_trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            // Only process lines that match the command format: "command-name  Description"
            // Commands consist of lowercase letters, numbers, slashes, hyphens, and underscores
            // Must start with alphanumeric to exclude list items (- text)
            // We trim the line first to exclude header lines that are right-padded
            if (preg_match('/^[a-z0-9][a-z0-9_\/\-]*\s+.+$/', $trimmedLine) !== 1) {
                continue;
            }

            $index = mb_strpos($line, ' ');
            if ($index === false) {
                continue;
            }
            $commands[] = mb_substr($line, 0, $index);
        }

        return $commands;
    }

    private static function getRoot(): string
    {
        $root = realpath(__DIR__.'/../..');
        if ($root === false) {
            throw new \LogicException('Failed to get root directory');
        }

        return $root;
    }

    private static function stripColoring(string $input): string
    {
        // Remove common ANSI escape sequences (CSI sequences) such as "\e[0;2m" or "\033[45m".
        // Use a general regex for CSI sequences and also strip OSC sequences if present.
        $output = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', $input);

        if ($output === null) {
            // preg_replace can return null on error — fall back to original input
            return $input;
        }

        // Remove Operating System Command (OSC) sequences (e.g. \x1B]...\x07 or \x1B\\)
        return preg_replace('/\x1B\].*?(?:\x07|\x1B\\\)/s', '', $output) ?? throw new \RuntimeException('Invalid escape sequence');
    }

    protected static function normalize(string $output): string
    {
        // Do not blindly convert backslashes to forward slashes — some outputs
        // contain PHP fully-qualified class names (with backslashes) that must
        // remain intact. Replace both Windows (backslash) and Unix (slash)
        // variants of known paths (root, home, platform) explicitly.

        $rootWin = self::getRoot();
        $rootUnix = str_replace('\\', '/', $rootWin);

        $replacements = [
            $rootWin,
            $rootUnix,
            '/home/user',
            'Common/Platform/'.\PHP_OS_FAMILY,
        ];

        $replaced = str_replace(
            $replacements,
            [
                '$ROOT',
                '$ROOT',
                '$HOME',
                'Common/Platform/$PLATFORM',
            ],
            $output,
        );

        // Collapse any "/foo/../" segments to keep canonical relative parts (e.g. "a/../../b" -> "../b")
        $lines = explode("\n", $replaced);
        foreach ($lines as &$line) {
            // Only attempt to collapse paths that contain $ROOT
            if (! str_contains($line, '$ROOT')) {
                continue;
            }

            // Collapse sequences like "$ROOT/tests/examples/../../resources/..."
            $linesParts = preg_split('/(\$ROOT[^\s,]*)/', $line, -1, \PREG_SPLIT_DELIM_CAPTURE);
            if ($linesParts === false) {
                throw new \RuntimeException('Invalid split');
            }
            for ($i = 1; $i < \count($linesParts); $i += 2) {
                $path = $linesParts[$i];
                // remove any trailing commas
                $suffix = '';
                if (str_ends_with($path, ',')) {
                    $path = mb_substr($path, 0, -1);
                    $suffix = ',';
                }

                // Collapse dot segments for the path portion
                $normalizedPath = self::collapseDotSegments(str_replace('\\', '/', $path));
                $linesParts[$i] = $normalizedPath.$suffix;
            }

            $line = implode('', $linesParts);
        }

        return implode("\n", $lines);
    }

    private static function collapseDotSegments(string $path): string
    {
        $parts = explode('/', $path);
        $stack = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                // preserve empty parts at start (e.g. leading $ROOT may include $ROOT)
                if ($part === '' && $stack === []) {
                    $stack[] = $part;
                }
                continue;
            }

            if ($part === '..') {
                if ($stack !== [] && end($stack) !== '..') {
                    array_pop($stack);
                    continue;
                }
                $stack[] = $part;
                continue;
            }

            $stack[] = $part;
        }

        return implode('/', $stack);
    }

    protected static function generateDockerComposeExecutionUser(): string
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            return '';
        }

        $uid = getmyuid();
        $gid = getmygid();
        if ($uid === false || $gid === false) {
            throw new \RuntimeException('Failed to get UID or GID');
        }

        return \sprintf('--user "%1$s:%2$s"', $uid, $gid);
    }
}
