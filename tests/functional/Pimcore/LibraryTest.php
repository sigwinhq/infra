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

namespace Sigwin\Infra\Test\Functional\Pimcore;

use Sigwin\Infra\Test\Functional\MakefileTestCase;
use Sigwin\Infra\Test\Functional\PHP\PhpTrait;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
final class LibraryTest extends MakefileTestCase
{
    use PhpTrait;

    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'Pimcore/common',
            'Pimcore/library',
        ];
    }

    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $paths = self::paths($env);

        $mkdir = $paths['mkdir: phpqa'];
        $testUnit = $paths['test: unit'];
        $testFunctional = $paths['test: functional library'];

        return [
            'help' => [self::generateHelpExecutionPath([
                __DIR__.'/../../../resources/Pimcore/library.mk',
                __DIR__.'/../../../resources/Pimcore/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdir, $paths['analyze']),
            'clean' => $paths['clean: library'],
            'dist' => array_merge($mkdir, $paths['prepareAndAnalyze'], $testUnit, $testFunctional),
            'setup/test' => array_merge($paths['docker compose: start library test'], $paths['touch: .env'], $paths['setup: Pimcore library test']),
            'sh/app' => $paths['shell: app library'],
            'sh/php' => array_merge($mkdir, $paths['shell: PHP']),
            'start/test' => $paths['docker compose: start library test'],
            'stop' => $paths['docker compose: stop Pimcore library'],
            'test' => array_merge($mkdir, $testUnit, $testFunctional),
            'test/functional' => $testFunctional,
            'test/unit' => array_merge($mkdir, $testUnit),
        ];
    }
}
