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
final class PharTest extends MakefileTestCase
{
    use PhpTrait;

    /**
     * @var array<string, string>
     */
    protected array $helpOverride = [
        'phar/build' => 'Build PHAR file',
    ];

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

    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'PHP/phar',
        ];
    }

    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $paths = self::paths($env);

        $mkdir = $paths['mkdir: phpqa'];
        $test = $paths['test: unit'];

        return [
            'help' => [self::generateHelpExecutionPath([
                __DIR__.'/../../../resources/PHP/phar.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'phar/build' => array_merge($mkdir, $paths['box: build']),
            'analyze' => array_merge($mkdir, $paths['analyze']),
            'dist' => array_merge($mkdir, $paths['prepareAndAnalyze'], $test),
            'sh/php' => array_merge($mkdir, $paths['shell: PHP']),
            'test' => array_merge($mkdir, $test),
        ];
    }
}
