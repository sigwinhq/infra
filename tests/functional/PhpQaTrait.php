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

trait PhpQaTrait
{
    private function generatePhpqaExecutionPath(string $command, ?float $phpVersion = null): string
    {
        $phpVersion ??= 8.1;

        return $this->normalize(sprintf(
            'docker run --init --interactive  --rm --env "COMPOSER_CACHE_DIR=/composer/cache" %2$s --volume "$ROOT/var/phpqa:/cache" --volume "$ROOT:/project" --volume "$HOME/.composer:/composer" --workdir /project jakzal/phpqa:1.83.2-php%3$s-alpine %1$s',
            sprintf($command, $phpVersion),
            \PHP_OS_FAMILY !== 'Windows' ? sprintf('--user "%1$s:%2$s"', getmyuid(), getmygid()) : '',
            $phpVersion
        ));
    }
}
