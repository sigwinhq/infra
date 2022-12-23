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
    private string $root;

    protected function setUp(): void
    {
        $this->root = realpath(__DIR__.'/../..');
    }

    public function testMakefileExists(): void
    {
        static::assertFileExists(
            $this->root.\DIRECTORY_SEPARATOR.$this->getMakefilePath()
        );
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
        string $directory = __DIR__.'/../..'
    ): string {
        $makefile = str_replace('/', \DIRECTORY_SEPARATOR, $makefile);
        $command = ['make', '-f', $this->root.\DIRECTORY_SEPARATOR.ltrim($makefile, '/\\')];
        if ($args !== null) {
            array_push($command, ...$args);
        }
        if ($makeCommand !== null) {
            $command[] = $makeCommand;
        }

        $process = new Process(
            $command,
            $directory,
            ['SIGWIN_INFRA_ROOT' => $this->root.\DIRECTORY_SEPARATOR.'resources'],
        );
        $process->mustRun();

        return str_replace($this->root, '~', $process->getOutput());
    }
}
