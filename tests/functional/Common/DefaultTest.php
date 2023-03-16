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
 *
 * @coversNothing
 *
 * @medium
 */
final class DefaultTest extends MakefileTestCase
{
    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
        ];
    }

    protected function getExpectedHelpCommandsExecutionPath(): array
    {
        return [
            'help' => [
                $this->generateHelpExecutionPath(),
            ],
        ];
    }
}
