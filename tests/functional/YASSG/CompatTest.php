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
 * @small
 */
final class CompatTest extends MakefileTestCase
{
    use PhpTrait;

    protected array $helpOverride = [
        'start' => 'Start app in APP_ENV mode (defaults to "dev")',
    ];

    protected function getExpectedHelpCommandsExecutionPath(): array
    {
        $mkdir = $this->paths()['mkdir: phpqa'];

        $lighthouse = [
            $this->generateDockerLighthouseExecutionPath('npx lhci autorun --config=lighthouse.config.json'),
        ];
        $test = [
            $this->generateDockerBackstopExecutionPath('test'),
        ];
        $reference = [
            $this->generateDockerBackstopExecutionPath('reference'),
        ];

        $build = [
            'mkdir -p public',
            'npm install',
            'BASE_URL=file://localhost$ROOT/public node_modules/.bin/encore production',
            'php vendor/sigwin/yassg/bin/yassg yassg:generate --env prod "file://localhost$ROOT/public" ""',
        ];

        $start = [
            'rm -rf var/cache/* var/log/* public',
            'make dev/assets dev/server -j2',
        ];

        return [
            'help' => [$this->generateHelpExecutionPath([
                __DIR__.'/../../../resources/YASSG/compat.mk',
                __DIR__.'/../../../resources/YASSG/common.mk',
                __DIR__.'/../../../resources/Visual/common.mk',
                __DIR__.'/../../../resources/Lighthouse/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdir, $this->paths()['analyze']),
            'analyze/lighthouse' => $lighthouse,
            'build' => $build,
            'dist' => array_merge($mkdir, $this->paths()['prepareAndAnalyze'], $test),
            'sh/php' => array_merge($mkdir, $this->paths()['shell: PHP']),
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
        ]);
    }

    public function testDevServerWorks(): void
    {
        $this->testMakefileCommandsWork('dev/server', [
            'ln -s vendor/sigwin/yassg/web/index.php',
            'symfony server:start --no-tls --document-root=. --port=9988',
        ]);
    }

    private function generateDockerBackstopExecutionPath(string $command): string
    {
        return sprintf(
            'docker run --init --interactive  --shm-size 256MB --cap-add=SYS_ADMIN --rm --env PROJECT_ROOT=$ROOT --env BASE_URL=file://localhost$ROOT/public %2$s --tmpfs /tmp --volume "$ROOT:$ROOT" --workdir "$ROOT" backstopjs/backstopjs:6.1.4 --config backstop.config.js %1$s',
            $command,
            $this->generateDockerComposeExecutionUser()
        );
    }

    private function generateDockerLighthouseExecutionPath(string $command): string
    {
        return sprintf(
            'docker run --init --interactive  --rm --env HOME=/tmp %2$s --volume "$ROOT:/project:ro" --workdir /project cypress/browsers:node18.12.0-chrome107 %1$s',
            $command,
            $this->generateDockerComposeExecutionUser()
        );
    }
}
