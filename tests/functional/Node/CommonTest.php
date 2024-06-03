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
        return [
            'help' => [self::generateHelpExecutionPath([
                __DIR__.'/../../../resources/Node/common.mk',
            ])],
            'sh/node' => [
                'mkdir -p $HOME/.npm',
                'docker run --init --interactive  --rm  --user "1000:1000" --volume "$ROOT:$ROOT" --volume "$HOME/.npm:/home/node/.npm" --workdir $ROOT node:21.7-alpine sh',
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
}
