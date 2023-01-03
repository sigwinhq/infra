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
 * @small
 */
final class DefaultTest extends MakefileTestCase
{
    protected function getExpectedHelp(): string
    {
        return <<<'EOF'
            [45mhelp                [0m Prints this help

            EOF;
    }

    protected function getExpectedHelpCommandsExecutionPath(): array
    {
        return [
            'help' => [
                match (\PHP_OS_FAMILY) {
                    'Darwin' => 'grep --no-filename --extended-regexp \'^ *[-a-zA-Z0-9_/]+ *:.*## \'  $ROOT/resources/Common/default.mk $ROOT/resources/Common/Platform/$PLATFORM/default.mk | sort | awk \'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $1, $2}\'',
                    'Linux' => 'grep -h -E \'^ *[-a-zA-Z0-9_/]+ *:.*## \' $ROOT/resources/Common/default.mk $ROOT/resources/Common/Platform/$PLATFORM/default.mk | sort | awk \'BEGIN {FS = ":.*?## "}; {printf "\033[45m%-20s\033[0m %s\n", $1, $2}\'',
                    'Windows' => 'Select-String -Pattern \'^ *(?<name>[-a-zA-Z0-9_/]+) *:.*## *(?<help>.+)\' $ROOT\resources\Common\default.mk,$ROOT\resources/Common/Platform/$PLATFORM/default.mk | ForEach-Object{"{0, -20}" -f $_.Matches[0].Groups["name"] | Write-Host -NoNewline -BackgroundColor Magenta -ForegroundColor White; " {0}" -f $_.Matches[0].Groups["help"] | Write-Host -ForegroundColor White}',
                    default => throw new \LogicException('Unknown OS family'),
                },
            ],
        ];
    }
}
