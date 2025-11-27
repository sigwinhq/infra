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

namespace Sigwin\Infra\Test\Functional;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\Group('example')]
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
final class PackageExampleTest extends MakefileTestCase
{
    public const MAKEFILE_PATH = __DIR__.'/../examples/package-example/Makefile';

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
        return [];
    }
}
