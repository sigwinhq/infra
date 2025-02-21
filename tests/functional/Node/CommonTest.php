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

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
final class CommonTest extends MakefileTestCase
{
    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $nodeVersion = $env['NODE_VERSION'] ?? '21.7';
        $nodeDockerImage = $env['NODE_DOCKER_IMAGE'] ?? 'node:%1$s-alpine';
        $dockerEnv = $env['DOCKER_ENV'] ?? ' ';

        return [
            'help' => [self::generateHelpExecutionPath([
                __DIR__.'/../../../resources/Node/common.mk',
            ])],
            'sh/node' => [
                'mkdir -p $HOME/.npm',
                self::generateNodeExecutionPath('sh', nodeVersion: $nodeVersion, dockerImage: $nodeDockerImage, env: $dockerEnv),
            ],
        ];
    }

    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'Node/common',
        ];
    }

    private static function generateNodeExecutionPath(string $command, string $nodeVersion, string $dockerImage, string $env): string
    {
        return self::normalize(\sprintf(
            'docker run --init --interactive  --rm %4$s%2$s --volume "$ROOT:$ROOT" --volume "$HOME/.npm:/home/node/.npm" --workdir "$ROOT" %3$s %1$s',
            \sprintf($command, $nodeVersion),
            self::generateDockerComposeExecutionUser(),
            \sprintf($dockerImage, $nodeVersion),
            $env
        ));
    }
}
