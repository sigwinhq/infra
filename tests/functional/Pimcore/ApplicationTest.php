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
use Symfony\Component\Filesystem\Filesystem;

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

    protected function setUp(): void
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir([
            'config/pimcore/classes',
            'public/var/assets',
            'public/var/tmp',
            'var/admin',
            'var/application-logger',
            'var/cache',
            'var/config',
            'var/email',
            'var/log',
            'var/tmp',
            'var/versions',
        ]);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove([
            'config/',
            'public/',
            'var/admin',
            'var/application-logger',
            'var/cache',
            'var/config',
            'var/email',
            'var/log',
            'var/tmp',
            'var/versions',
        ]);
    }

    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'Pimcore/common',
            'Pimcore/application',
        ];
    }

    protected function getExpectedHelpCommandsExecutionPath(): array
    {
        $mkdir = $this->paths()['mkdir: phpqa'];
        $clean = $this->paths()['clean: Pimcore application'];
        $testUnit = $this->paths()['test: unit'];
        $testFunctional = $this->paths()['test: functional app'];

        return [
            'help' => [$this->generateHelpExecutionPath([
                __DIR__.'/../../../resources/Pimcore/application.mk',
                __DIR__.'/../../../resources/Pimcore/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdir, $this->paths()['analyze']),
            'build/dev' => $this->paths()['build: dev'],
            'build/prod' => $this->paths()['build: prod'],
            'clean' => $clean,
            'dist' => array_merge($mkdir, $this->paths()['prepareAndAnalyze'], $testUnit, $testFunctional),
            'setup/filesystem' => array_merge($this->paths()['mkdir: composer'], $clean, $this->paths()['permissions: Pimcore']),
            'setup/test' => $this->paths()['setup: Pimcore app test'],
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
        ];
    }
}
