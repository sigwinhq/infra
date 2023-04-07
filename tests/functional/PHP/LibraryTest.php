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
 *
 * @coversNothing
 *
 * @medium
 */
final class LibraryTest extends MakefileTestCase
{
    use PhpTrait;

    public function testCanRunComposerInstallLowest(): void
    {
        $paths = $this->paths(null);

        $mkdir = $paths['mkdir: phpqa'];
        $composer = $paths['composer: install-lowest'];
        $expected = array_merge($mkdir, $composer);
        $actual = $this->dryRun('composer/install-lowest');

        static::assertSame($expected, $actual);
    }

    public function testCanRunComposerInstallHighest(): void
    {
        $paths = $this->paths(null);

        $mkdir = $paths['mkdir: phpqa'];
        $composer = $paths['composer: install-highest'];
        $expected = array_merge($mkdir, $composer);
        $actual = $this->dryRun('composer/install-highest');

        static::assertSame($expected, $actual);
    }

    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'PHP/library',
        ];
    }

    protected function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $paths = $this->paths($env);

        $mkdir = $paths['mkdir: phpqa'];
        $test = $paths['test: unit'];

        return [
            'help' => [$this->generateHelpExecutionPath([
                __DIR__.'/../../../resources/PHP/library.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdir, $paths['analyze']),
            'dist' => array_merge($mkdir, $paths['prepareAndAnalyze'], $test),
            'sh/php' => array_merge($mkdir, $paths['shell: PHP']),
            'test' => array_merge($mkdir, $test),
        ];
    }
}
