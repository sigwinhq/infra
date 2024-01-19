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

namespace Sigwin\Infra\Test\Functional\YASSG;

use Sigwin\Infra\Test\Functional\MakefileTestCase;
use Sigwin\Infra\Test\Functional\PHP\PhpTrait;

/**
 * @internal
 *
 * @coversNothing
 *
 * @medium
 */
final class DefaultTest extends MakefileTestCase
{
    use PhpTrait;

    /**
     * @var array<string, string>
     */
    protected array $helpOverride = [
        'start' => 'Start app in APP_ENV mode (defaults to "dev")',
        'build/docker' => 'Build app for "APP_ENV" target (defaults to "prod") fully in Docker',
    ];

    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'YASSG/common',
            'YASSG/default',
        ];
    }

    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $paths = self::paths($env);

        $mkdir = $paths['mkdir: phpqa'];
        $build = [
            'mkdir -p public',
            'npm install',
            'BASE_URL=file://localhost$ROOT/public node_modules/.bin/encore production',
            'YASSG_SKIP_BUNDLES= php vendor/sigwin/yassg/bin/yassg yassg:generate --env prod "file://localhost$ROOT/public" ',
        ];

        $start = [
            'rm -rf var/cache/* var/log/* public',
            'make dev/assets dev/server dev/compose -j3',
        ];

        return [
            'help' => [self::generateHelpExecutionPath([
                __DIR__.'/../../../resources/YASSG/default.mk',
                __DIR__.'/../../../resources/YASSG/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'build' => $build,
            'build/docker' => [
                'docker compose up imgproxy -d',
                'docker compose run --rm webpack npm ci',
                'docker compose run --rm webpack npx encore production',
                'docker compose run --rm --env IMGPROXY_URL=http://imgproxy:8080 app vendor/sigwin/yassg/bin/yassg yassg:generate --env prod "file://localhost$ROOT/public"',
            ],
            'sh/php' => array_merge($mkdir, $paths['shell: PHP']),
            'start' => $start,
            'start/dev' => $start,
        ];
    }
}
