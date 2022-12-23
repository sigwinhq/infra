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

namespace Sigwin\functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @coversNothing
 *
 * @internal
 *
 * @small
 */
final class MakefileTest extends TestCase
{
    public function testTrue(): void
    {
        $out = self::execute(
            'resources/Common/default.mk',
        );

        $out = self::dryRun(
            'resources/Common/Platform/Linux/default.mk',
            'help',
        );
        static::assertTrue(true);
    }

    private static function dryRun(
        string $makefile,
        ?string $makeCommand = null,
        ?array $args = null,
        string $directory = __DIR__.'/../..'
    ): array {
        $args[] = '--dry-run';

        return array_filter(explode("\n", self::execute($makefile, $makeCommand, $args, $directory)));
    }

    private static function execute(
        string $makefile,
        ?string $makeCommand = null,
        ?array $args = null,
        string $directory = __DIR__.'/../..'
    ): string {
        $root = realpath(__DIR__.'/../..');

        $command = ['make', '-f', $root.'/'.ltrim($makefile, '/')];
        if ($args !== null) {
            array_push($command, ...$args);
        }
        if ($makeCommand !== null) {
            $command[] = $makeCommand;
        }

        $process = new Process(
            $command,
            $directory,
            ['SIGWIN_INFRA_ROOT' => $root.'/resources'],
        );
        $process->mustRun();

        return str_replace($root, '~', $process->getOutput());
    }
}
