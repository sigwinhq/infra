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
#[\PHPUnit\Framework\Attributes\Group('help')]
#[\PHPUnit\Framework\Attributes\CoversNothing]
#[\PHPUnit\Framework\Attributes\Medium]
final class MakefileExampleTest extends MakefileTestCase
{
    public const MAKEFILE_PATH = __DIR__.'/../examples/makefile-example/Makefile';

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
            'help' => self::generateHelpExecutionPathArray(),
        ];
    }

    #[\Override]
    protected function generateHelpHeader(): string
    {
        // The makefile-example exports environment variables directly in the Makefile
        // These are used to populate the help header
        return "\n"
            ."\e[42mExample Makefile Project                                                      \e[0m\n"
            ."\e[0;2mExample project using Makefile variables for help metadata\e[0m\n"
            ."\e[0;2mLocal:\e[0m\n"
            ."  - http://localhost:9000 \e[0;2m(Dev server)\e[0m\n"
            ."  - http://localhost:9001 \e[0;2m(Admin panel)\e[0m\n"
            ."\e[0;2mHomepage:\e[0m   https://example.com/makefile\n"
            ."\e[0;2mRepository:\e[0m https://github.com/example/makefile-project\n"
            ."\n";
    }
}
