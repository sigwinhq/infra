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
 * @coversNothing
 *
 * @internal
 *
 * @medium
 */
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
        'clean' => 'Clear logs and system cache',
        'dist' => 'Prepare the codebase for commit',
        'help' => 'Prints this help',
        'setup/filesystem' => 'Setup: filesystem (var, public/var folders)',
        'setup/test' => 'Setup: create a functional test runtime',
        'sh/app' => 'Run application shell',
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
     * @var array<string, string>
     */
    protected array $helpOverride = [];

    /**
     * @param null|array<string, string> $env
     *
     * @return array<string, list<string>>
     */
    abstract protected function getExpectedHelpCommandsExecutionPath(?array $env = null): array;

    /**
     * @return list<string>
     */
    abstract protected function getExpectedInitPaths(): array;

    public function testMakefileExists(): void
    {
        self::assertFileExists(
            $this->getRoot().\DIRECTORY_SEPARATOR.$this->getMakefilePath()
        );
    }

    public function testMakefileHasHelp(): void
    {
        $actual = $this->getMakefileHelp();
        $expected = $this->getExpectedHelp();

        if (\PHP_OS_FAMILY === 'Windows') {
            $expected = preg_replace('/\r\n|\r|\n/', "\n", $this->stripColoring($expected));
        }

        self::assertSame($expected, $actual);
    }

    public function testHelpIsTheDefaultCommand(): void
    {
        $expected = $this->dryRun('help');
        $actual = $this->dryRun();

        self::assertSame($expected, $actual);
    }

    public function testMakefileHasInit(): void
    {
        $expected = array_map(static fn (string $path): string => sprintf('if [ -d "$ROOT/resources/%1$s" ]; then cp -a $ROOT/resources/%1$s/. .; fi', $path), $this->getExpectedInitPaths());
        $expected = array_merge(...array_values(array_map(static fn ($value) => [$value, 'if [ -f .gitattributes.dist ]; then mv .gitattributes.dist .gitattributes; fi'], $expected)));
        $actual = $this->dryRun('init');

        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider provideMakefileCommandsWorkCases
     *
     * @param array<string, string> $env
     */
    public function testMakefileCommandsWork(string $command, array $expected, array $env): void
    {
        $actual = $this->dryRun($command, env: $env);

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array<string, string>>
     */
    protected function getEnvs(): iterable
    {
        yield [];
    }

    protected function getExpectedHelp(): string
    {
        return $this->generateHelpList(array_keys($this->getExpectedHelpCommandsExecutionPath([])));
    }

    protected function generateHelpList(array $commands): string
    {
        $help = [];
        sort($commands);
        foreach ($commands as $command) {
            $help[] = sprintf('%1$s[45m%2$s%1$s[0m %3$s', "\e", str_pad($command, 20), $this->helpOverride[$command] ?? $this->help[$command] ?? '');
        }

        return implode("\n", $help)."\n";
    }

    protected function generateHelpExecutionPath(array $files = [], array $additionalFiles = []): string
    {
        $files = array_merge($files, [
            __DIR__.'/../../resources/Common/default.mk',
            __DIR__.'/../../resources/Common/Platform/'.\PHP_OS_FAMILY.'/default.mk',
        ]);
        $files = array_map('realpath', $files);
        $files = array_merge($files, $additionalFiles);

        $command = match (\PHP_OS_FAMILY) {
            'Darwin' => 'grep --no-filename --extended-regexp \'^ *[-a-zA-Z0-9_/]+ *:.*## \'  '.implode(' ', $files).' | awk \'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $1, $2}\' | sort',
            'Linux' => 'grep -h -E \'^ *[-a-zA-Z0-9_/]+ *:.*## \' '.implode(' ', $files).' | awk \'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $1, $2}\' | sort',
            'Windows' => 'Select-String -Pattern \'^ *(?<name>[-a-zA-Z0-9_/]+) *:.*## *(?<help>.+)\' '.implode(',', array_map(function (string $item, int $index): string {
                if ($index === 0) {
                    return $item;
                }

                return str_replace('$ROOT/resources', '$ROOT\\resources', str_replace('\\', '/', $this->normalize($item)));
            }, $files, array_keys($files))).' | Sort-Object {$_.Matches[0].Groups["name"]} | ForEach-Object{"{0, -20}" -f $_.Matches[0].Groups["name"] | Write-Host -NoNewline -BackgroundColor Magenta -ForegroundColor White; " {0}" -f $_.Matches[0].Groups["help"] | Write-Host -ForegroundColor White}',
            default => throw new \LogicException('Unknown OS family'),
        };

        return $this->normalize($command);
    }

    protected function generatePermissionsExecutionPath(array $dirs): array
    {
        $commands = [];
        foreach ($dirs as $dir) {
            $commands[] = sprintf('mkdir -p %1$s', $dir);
            if (\PHP_OS_FAMILY === 'Linux') {
                $commands[] = sprintf('setfacl -dRm          m:rwX  %1$s', $dir);
                $commands[] = sprintf('setfacl -Rm           m:rwX  %1$s', $dir);
                $commands[] = sprintf('setfacl -dRm u:`whoami`:rwX  %1$s', $dir);
                $commands[] = sprintf('setfacl -Rm  u:`whoami`:rwX  %1$s', $dir);
                $commands[] = sprintf('setfacl -dRm u:999:rwX %1$s', $dir);
                $commands[] = sprintf('setfacl -Rm  u:999:rwX %1$s', $dir);
                $commands[] = sprintf('setfacl -dRm u:root:rwX      %1$s', $dir);
                $commands[] = sprintf('setfacl -Rm  u:root:rwX      %1$s', $dir);
            }
        }

        return $commands;
    }

    /**
     * @return iterable<array-key, array{string, list<string>, array<string, string>}>
     */
    public function provideMakefileCommandsWorkCases(): iterable
    {
        $commands = $this->getMakefileHelpCommands();

        $envs = $this->getEnvs();
        foreach ($envs as $env) {
            $expected = $this->getExpectedHelpCommandsExecutionPath($env);
            foreach ($commands as $command) {
                self::assertArrayHasKey($command, $expected, sprintf('No expected execution path defined for command "%1$s"', $command));
            }

            foreach ($expected as $command => $path) {
                yield [$command, $path, $env];
            }
        }
    }

    protected function getMakefilePath(): string
    {
        $path = str_replace([__NAMESPACE__.'\\', '\\'], ['', \DIRECTORY_SEPARATOR], static::class);
        $dir = pathinfo($path, \PATHINFO_DIRNAME);
        $name = pathinfo($path, \PATHINFO_FILENAME);
        if (! str_ends_with($name, 'Test')) {
            throw new \LogicException('Invalid test class name, expected to end with "Test"');
        }
        $name = mb_substr($name, 0, -4);

        return sprintf('resources%2$s%1$s%2$s%3$s.mk', $dir, \DIRECTORY_SEPARATOR, mb_strtolower($name));
    }

    /**
     * @param null|array<string, int|string> $env
     */
    protected function dryRun(
        ?string $makeCommand = null,
        ?array $args = null,
        ?array $env = null,
        ?string $makefile = null,
        string $directory = __DIR__.'/../..'
    ): array {
        $args[] = '--dry-run';

        return array_filter(explode("\n", $this->execute($makeCommand, $args, $env, $makefile, $directory)));
    }

    /**
     * @param null|array<string, int|string> $env
     */
    protected function execute(
        ?string $command = null,
        ?array $args = null,
        ?array $env = null,
        ?string $makefile = null,
        string $directory = __DIR__.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'
    ): string {
        $makefile = str_replace('/', \DIRECTORY_SEPARATOR, $makefile ?? $this->getMakefilePath());
        $fullCommand = ['make', '-f', $this->getRoot().\DIRECTORY_SEPARATOR.ltrim($makefile, '/\\')];
        if ($args !== null) {
            array_push($fullCommand, ...$args);
        }
        if ($command !== null) {
            $fullCommand[] = $command;
        }

        /** @var string $directory */
        $directory = realpath($directory);
        $process = new Process(
            $fullCommand,
            $directory,
            array_replace([
                'HOME' => '/home/user',
                'SIGWIN_INFRA_ROOT' => $this->getRoot().\DIRECTORY_SEPARATOR.'resources',

                // streamline these to ensure consistent runtime environment
                'RUNNER' => '999',
                'APP_ENV' => 'env',
                'APP_ROOT' => $this->getRoot(),
                'PHP_VERSION' => '',
                'GITHUB_ACTIONS' => '',
                'COMPOSE_PROJECT_NAME' => 'infra',
                'PIMCORE_KERNEL_CLASS' => 'App\\Kernel',
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

        return $this->normalize($output);
    }

    private function getMakefileHelp(): string
    {
        return $this->execute('help');
    }

    /**
     * @return list<string>
     */
    private function getMakefileHelpCommands(): array
    {
        $help = explode("\n", trim($this->stripColoring($this->getMakefileHelp())));

        $commands = [];
        foreach ($help as $command) {
            $index = mb_strpos($command, ' ');
            if ($index === false) {
                throw new \LogicException('Invalid command');
            }
            $commands[] = mb_substr($command, 0, $index);
        }

        return $commands;
    }

    private function getRoot(): string
    {
        /** @var string $root */
        $root = realpath(__DIR__.'/../..');

        return $root;
    }

    private function stripColoring(string $input): string
    {
        /** @var string $output */
        $output = preg_replace('/\033\[\d+m/', '', $input);

        return $output;
    }

    protected function normalize(string $output): string
    {
        return str_replace(
            [
                $this->getRoot(),
                str_replace('\\', '/', $this->getRoot()),
                '/home/user',
                'Common/Platform/'.\PHP_OS_FAMILY,
            ],
            [
                '$ROOT',
                '$ROOT',
                '$HOME',
                'Common/Platform/$PLATFORM',
            ],
            $output,
        );
    }

    protected function generateDockerComposeExecutionUser(): string
    {
        return \PHP_OS_FAMILY !== 'Windows' ? sprintf('--user "%1$s:%2$s"', getmyuid(), getmygid()) : '';
    }
}
