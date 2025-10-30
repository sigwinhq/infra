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

namespace Sigwin\Infra\Test\Functional\Common;

use Sigwin\Infra\Test\Functional\MakefileTestCase;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
final class DefaultTest extends MakefileTestCase
{
    #[\Override]
    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
        ];
    }

    #[\Override]
    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        return [
            'help' => [
                self::generateHelpExecutionPath(),
            ],
        ];
    }
}
