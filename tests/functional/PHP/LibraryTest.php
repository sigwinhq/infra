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

    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'PHP/library',
        ];
    }

    protected function getExpectedHelpCommandsExecutionPath(): array
    {
        $mkdir = $this->paths()['mkdir: phpqa'];
        $test = $this->paths()['test: unit'];

        return [
            'help' => [$this->generateHelpExecutionPath([
                __DIR__.'/../../../resources/PHP/library.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdir, $this->paths()['analyze']),
            'dist' => array_merge($mkdir, $this->paths()['prepareAndAnalyze'], $test),
            'sh/php' => array_merge($mkdir, $this->paths()['shell: PHP']),
            'test' => array_merge($mkdir, $test),
        ];
    }
}
