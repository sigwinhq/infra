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
 * @small
 */
final class ApplicationTest extends MakefileTestCase
{
    use PhpTrait;

    protected function getExpectedHelpCommandsExecutionPath(): array
    {
        $mkdir = $this->paths()['mkdir'];
        $testUnit = $this->paths()['test: unit'];
        $testFunctional = $this->paths()['test: functional'];

        return [
            'help' => [$this->generateHelpExecutionPath([
                __DIR__.'/../../../resources/Pimcore/application.mk',
                __DIR__.'/../../../resources/Pimcore/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdir, $this->paths()['analyze']),
            'clean' => $this->paths()['clean: Pimcore application'],
            'dist' => array_merge($mkdir, $this->paths()['prepareAndAnalyze'], $testUnit, $testFunctional),
            'setup/test' => array_merge($this->paths()['docker compose: start library test'], $this->paths()['touch'], $this->paths()['setup: Pimcore test']),
            'sh/app' => $this->paths()['shell: app'],
            'sh/php' => array_merge($mkdir, $this->paths()['shell: PHP']),
            'start' => $this->paths()['docker compose: start app'],
            'start/dev' => $this->paths()['docker compose: start app dev'],
            'start/prod' => $this->paths()['docker compose: start app prod'],
            'start/test' => $this->paths()['docker compose: start app test'],
            'stop' => $this->paths()['docker compose: stop Pimcore app'],
            'test' => array_merge($mkdir, $testUnit, $testFunctional),
            'test/functional' => $testFunctional,
            'test/unit' => array_merge($mkdir, $testUnit),

            'build/dev' => $this->paths()['build: dev'],
            'build/prod' => $this->paths()['build: prod'],
            'setup/filesystem' => [],
        ];
    }
}
