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
final class CompatTest extends MakefileTestCase
{
    use PhpTrait;

    /**
     * @var array<string, string>
     */
    protected array $helpOverride = [
        'start' => 'Start app in APP_ENV mode (defaults to "dev")',
    ];

    protected function getExpectedInitPaths(): array
    {
        return [
            'Common/Platform/$PLATFORM/default',
            'Common/default',
            'PHP/common',
            'Lighthouse/common',
            'Visual/common',
            'YASSG/common',
            'YASSG/compat',
        ];
    }

    protected static function getExpectedHelpCommandsExecutionPath(?array $env = null): array
    {
        $paths = self::paths($env);

        $mkdir = $paths['mkdir: phpqa'];

        $lighthouse = [
            self::generateDockerLighthouseExecutionPath('npx lhci autorun --config=lighthouse.config.json'),
        ];
        $test = [
            self::generateDockerBackstopExecutionPath('test'),
        ];
        $reference = [
            self::generateDockerBackstopExecutionPath('reference'),
        ];

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
                __DIR__.'/../../../resources/YASSG/compat.mk',
                __DIR__.'/../../../resources/YASSG/common.mk',
                __DIR__.'/../../../resources/Visual/common.mk',
                __DIR__.'/../../../resources/Lighthouse/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdir, $paths['analyze']),
            'analyze/lighthouse' => $lighthouse,
            'build' => $build,
            'dist' => array_merge($mkdir, $paths['prepareAndAnalyze'], $test),
            'sh/php' => array_merge($mkdir, $paths['shell: PHP']),
            'start' => $start,
            'start/dev' => $start,
            'test' => $test,
            'visual/reference' => $reference,
        ];
    }

    public function testDevAssetsWorks(): void
    {
        $this->testMakefileCommandsWork('dev/assets', [
            'npm install',
            'node_modules/.bin/encore dev-server',
        ], []);
    }

    public function testDevServerWorks(): void
    {
        $this->testMakefileCommandsWork('dev/server', [
            'ln -s vendor/sigwin/yassg/web/index.php',
            'YASSG_SKIP_BUNDLES= symfony server:start --no-tls --document-root=. --port=9988',
        ], []);
    }

    public function testDevComposeWorks(): void
    {
        $this->testMakefileCommandsWork('dev/compose', [
            'docker compose up',
        ], []);
    }

    private static function generateDockerBackstopExecutionPath(string $command): string
    {
        return sprintf(
            'docker run --init --interactive  --shm-size 256MB --cap-add=SYS_ADMIN --rm --env PROJECT_ROOT=$ROOT --env BASE_URL=file://localhost$ROOT/public %2$s --tmpfs /tmp --volume "$ROOT:$ROOT" --workdir "$ROOT" backstopjs/backstopjs:6.3.3 --config backstop.config.js %1$s',
            $command,
            self::generateDockerComposeExecutionUser()
        );
    }

    private static function generateDockerLighthouseExecutionPath(string $command): string
    {
        return sprintf(
            'docker run --init --interactive  --rm --env HOME=/tmp %2$s --volume "$ROOT:/public" --workdir "/public" cypress/browsers:node-20.10.0-chrome-118.0.5993.88-1-ff-118.0.2-edge-118.0.2088.46-1 %1$s',
            $command,
            self::generateDockerComposeExecutionUser()
        );
    }
}
