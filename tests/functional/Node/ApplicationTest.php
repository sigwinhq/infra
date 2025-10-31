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

namespace Sigwin\Infra\Test\Functional\Node;

use Sigwin\Infra\Test\Functional\MakefileTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
final class ApplicationTest extends MakefileTestCase
{
    use NodeTrait;

    #[\Override]
    protected function setUp(): void
    {
        $filesystem = new Filesystem();
        $filesystem->touch('.env.dist');
        $filesystem->mkdir([
            'var/cache',
            'var/log',
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove([
            '.env.dist',
            '.env',
            'var/log',
            'var/cache',
        ]);
    }

    #[\Override]
    protected function getExpectedInitPaths(): array
    {
        return [
            'Secrets/common',
            'Compose/common',
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'Node/common',
            'Node/application',
        ];
    }

    #[\Override]
    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $paths = self::paths($env);

        $mkdir = $paths['mkdir: npm'];
        $clean = $paths['clean: Node application'];
        $testUnit = $paths['test: unit'];
        $testFunctional = $paths['test: functional app'];

        return [
            'help' => [self::generateHelpExecutionPath([
                __DIR__.'/../../../resources/Node/application.mk',
                __DIR__.'/../../../resources/Node/common.mk',
            ], [
                __DIR__.'/../../../resources/Compose/common.mk',
                __DIR__.'/../../../resources/Secrets/common.mk',
            ])],
            'analyze' => $paths['analyze'],
            'build/dev' => $paths['build: dev'],
            'build/prod' => $paths['build: prod'],
            'clean' => $clean,
            'dist' => array_merge($paths['analyze'], $testUnit, $testFunctional),
            'setup/filesystem' => array_merge($mkdir, $clean, $paths['permissions: Node']),
            'setup/test' => array_merge($mkdir, $clean, $paths['permissions: Node'], [
                self::generateDockerComposeAppExecExecutionPath('npm run setup:test', 'test'),
            ]),
            'sh/app' => $paths['shell: app'],
            'sh/node' => array_merge($mkdir, $paths['shell: node']),
            'start' => $paths['docker compose: start app'],
            'start/dev' => $paths['docker compose: start app dev'],
            'start/prod' => $paths['docker compose: start app prod'],
            'start/test' => $paths['docker compose: start app test'],
            'stop' => $paths['docker compose: stop app'],
            'test' => array_merge($testUnit, $testFunctional),
            'test/functional' => $testFunctional,
            'test/unit' => $testUnit,
        ];
    }
}
