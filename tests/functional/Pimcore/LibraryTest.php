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
            'Pimcore/common',
            'Pimcore/library',
        ];
    }

    protected function getExpectedHelpCommandsExecutionPath(): array
    {
        $mkdir = $this->paths()['mkdir: phpqa'];
        $testUnit = $this->paths()['test: unit'];
        $testFunctional = $this->paths()['test: functional library'];

        return [
            'help' => [$this->generateHelpExecutionPath([
                __DIR__.'/../../../resources/Pimcore/library.mk',
                __DIR__.'/../../../resources/Pimcore/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdir, $this->paths()['analyze']),
            'clean' => $this->paths()['clean: library'],
            'dist' => array_merge($mkdir, $this->paths()['prepareAndAnalyze'], $testUnit, $testFunctional),
            'setup/test' => array_merge($this->paths()['docker compose: start library test'], $this->paths()['touch'], $this->paths()['setup: Pimcore library test']),
            'sh/app' => $this->paths()['shell: app library'],
            'sh/php' => array_merge($mkdir, $this->paths()['shell: PHP']),
            'start/test' => $this->paths()['docker compose: start library test'],
            'stop' => $this->paths()['docker compose: stop Pimcore library'],
            'test' => array_merge($mkdir, $testUnit, $testFunctional),
            'test/functional' => $testFunctional,
            'test/unit' => array_merge($mkdir, $testUnit),
        ];
    }
}
