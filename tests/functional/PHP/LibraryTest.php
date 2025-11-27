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

namespace Sigwin\Infra\Test\Functional\PHP;

use Sigwin\Infra\Test\Functional\MakefileTestCase;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
final class LibraryTest extends MakefileTestCase
{
    use PhpTrait;

    public function testCanRunComposerInstallLowest(): void
    {
        $paths = self::paths(null);

        $mkdir = $paths['mkdir: phpqa'];
        $composer = $paths['composer: install-lowest'];
        $expected = array_merge($mkdir, $composer);
        $actual = self::dryRun('composer/install-lowest');

        self::assertSame($expected, $actual);
    }

    public function testCanRunComposerInstallHighest(): void
    {
        $paths = self::paths(null);

        $mkdir = $paths['mkdir: phpqa'];
        $composer = $paths['composer: install-highest'];
        $expected = array_merge($mkdir, $composer);
        $actual = self::dryRun('composer/install-highest');

        self::assertSame($expected, $actual);
    }

    #[\Override]
    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'PHP/library',
        ];
    }

    #[\Override]
    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $paths = self::paths($env);

        $mkdir = $paths['mkdir: phpqa'];
        $test = $paths['test: unit'];

        return [
            'analyze' => array_merge($mkdir, $paths['analyze']),
            'dist' => array_merge($mkdir, $paths['prepareAndAnalyze'], $test),
            'sh/php' => array_merge($mkdir, $paths['shell: PHP']),
            'test' => array_merge($mkdir, $test),
        ];
    }
}
