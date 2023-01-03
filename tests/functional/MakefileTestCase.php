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
use Symfony\Component\Process\Process;

/**
 * @coversNothing
 *
 * @internal
 *
 * @small
 */
abstract class MakefileTestCase extends TestCase
{
    private const HELP_MAP = [
        'help' => 'Prints this help',
    ];

    abstract protected function getExpectedHelp(): string;

    abstract protected function getExpectedHelpCommandsExecutionPath(): array;

    public function testMakefileExists(): void
    {
        static::assertFileExists(
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

        static::assertSame($expected, $actual);
    }

    /**
     * @dataProvider generateHelpCommandsExecutionPathFixtures
     */
    public function testMakefileHelpCommandsWork(string $command, array $expected): void
    {
        $actual = $this->dryRun($this->getMakefilePath(), $command);

        static::assertSame($expected, $actual);
    }

    protected function generateExpectedHelpList(array $commands): string
    {
        $help = [];
        foreach ($commands as $command) {
            $help[] = sprintf('%1$s[45m%2$s%1$s[0m %3$s', "\e", str_pad($command, 20), self::HELP_MAP[$command]);
        }

        return implode("\n", $help)."\n";
    }

    protected function generateExpectedHelpExecutionPath(array $files = []): string
    {
        $files = [
            '$ROOT/resources/Common/default.mk',
            '$ROOT/resources/Common/Platform/$PLATFORM/default.mk',
        ];

        return match (\PHP_OS_FAMILY) {
            'Darwin' => 'grep --no-filename --extended-regexp \'^ *[-a-zA-Z0-9_/]+ *:.*## \'  '.implode(' ', $files).' | sort | awk \'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $1, $2}\'',
            'Linux' => 'grep -h -E \'^ *[-a-zA-Z0-9_/]+ *:.*## \' '.implode(' ', $files).' | sort | awk \'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $1, $2}\'',
            'Windows' => 'Select-String -Pattern \'^ *(?<name>[-a-zA-Z0-9_/]+) *:.*## *(?<help>.+)\' '.implode(' ', $files).' | ForEach-Object{"{0, -20}" -f $_.Matches[0].Groups["name"] | Write-Host -NoNewline -BackgroundColor Magenta -ForegroundColor White; " {0}" -f $_.Matches[0].Groups["help"] | Write-Host -ForegroundColor White}',
            default => throw new \LogicException('Unknown OS family'),
        };
    }

    private function generateHelpCommandsExecutionPathFixtures(): array
    {
        $expected = $this->getExpectedHelpCommandsExecutionPath();

        $commands = $this->getMakefileHelpCommands();
        foreach ($commands as $command) {
            static::assertArrayHasKey($command, $expected, sprintf('No expected execution path defined for command "%1$s"', $command));
        }

        $fixtures = [];
        foreach ($expected as $command => $path) {
            $fixtures[] = [$command, $path];
        }

        return $fixtures;
    }

    private function getMakefilePath(): string
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

    private function dryRun(
        string $makefile,
        ?string $makeCommand = null,
        ?array $args = null,
        string $directory = __DIR__.'/../..'
    ): array {
        $args[] = '--dry-run';

        return array_filter(explode("\n", $this->execute($makefile, $makeCommand, $args, $directory)));
    }

    private function execute(
        string $makefile,
        ?string $makeCommand = null,
        ?array $args = null,
        string $directory = __DIR__.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'
    ): string {
        $makefile = str_replace('/', \DIRECTORY_SEPARATOR, $makefile);
        $command = ['make', '-f', $this->getRoot().\DIRECTORY_SEPARATOR.ltrim($makefile, '/\\')];
        if ($args !== null) {
            array_push($command, ...$args);
        }
        if ($makeCommand !== null) {
            $command[] = $makeCommand;
        }

        /** @var string $directory */
        $directory = realpath($directory);
        $process = new Process(
            $command,
            $directory,
            ['SIGWIN_INFRA_ROOT' => $this->getRoot().\DIRECTORY_SEPARATOR.'resources'],
        );
        $process->mustRun();
        $output = $process->getOutput();

        if (\PHP_OS_FAMILY === 'Windows') {
            /** @var string $output */
            $output = preg_replace('/\r\n|\r|\n/', "\n", $output);
        }

        return str_replace(
            [
                $this->getRoot(),
                'Common/Platform/'.\PHP_OS_FAMILY,
            ],
            [
                '$ROOT',
                'Common/Platform/$PLATFORM',
            ],
            $output,
        );
    }

    private function getMakefileHelp(): string
    {
        return $this->execute($this->getMakefilePath(), 'help');
    }

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
}
