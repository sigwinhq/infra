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
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
final class ApplicationTest extends MakefileTestCase
{
    use PhpTrait;

    public function testCanRunComposerInstall(): void
    {
        $filesystem = new Filesystem();
        $filesystem->rename('composer.lock', 'composer.lock.old');

        $paths = self::paths(null);

        $mkdir = $paths['mkdir: phpqa'];
        $composer = $paths['composer: install'];
        $touch = $paths['touch: composer.lock'];
        $expected = array_merge($mkdir, $composer, $touch);

        $actual = self::dryRun('composer/install');

        $filesystem->rename('composer.lock.old', 'composer.lock');

        self::assertSame($expected, $actual);
    }

    protected function setUp(): void
    {
        $filesystem = new Filesystem();
        $filesystem->touch('.env');
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
            '.env',
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
            'Secrets/common',
            'Compose/common',
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'Pimcore/common',
            'Pimcore/application',
        ];
    }

    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $paths = self::paths($env);

        $mkdir = $paths['mkdir: phpqa'];
        $clean = $paths['clean: Pimcore application'];
        $testUnit = $paths['test: unit'];
        $testFunctional = $paths['test: functional app'];

        return [
            'help' => [self::generateHelpExecutionPath([
                __DIR__.'/../../../resources/Pimcore/application.mk',
                __DIR__.'/../../../resources/Pimcore/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ], [
                __DIR__.'/../../../resources/Compose/common.mk',
                __DIR__.'/../../../resources/Secrets/common.mk',
                '.env',
            ])],
            'analyze' => array_merge($mkdir, $paths['analyze']),
            'build/dev' => $paths['build: dev'],
            'build/prod' => $paths['build: prod'],
            'clean' => $clean,
            'dist' => array_merge($mkdir, $paths['prepareAndAnalyze'], $testUnit, $testFunctional),
            'setup/filesystem' => array_merge($paths['mkdir: composer'], $clean, $paths['permissions: Pimcore']),
            'setup/test' => $paths['setup: Pimcore app test'],
            'sh/app' => $paths['shell: app'],
            'sh/php' => array_merge($mkdir, $paths['shell: PHP']),
            'start' => $paths['docker compose: start app'],
            'start/dev' => $paths['docker compose: start app dev'],
            'start/prod' => $paths['docker compose: start app prod'],
            'start/test' => $paths['docker compose: start app test'],
            'stop' => $paths['docker compose: stop Pimcore app'],
            'test' => array_merge($mkdir, $testUnit, $testFunctional),
            'test/functional' => $testFunctional,
            'test/unit' => array_merge($mkdir, $testUnit),
        ];
    }
}
