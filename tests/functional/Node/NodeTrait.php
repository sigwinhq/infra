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

trait NodeTrait
{
    /**
     * @return iterable<array<string, string>>
     */
    protected static function getEnvs(): iterable
    {
        yield ['NODE_VERSION' => '22.0'];
        yield ['NODE_VERSION' => '23.0'];
        yield ['NODE_VERSION' => '25.1'];

        yield ['NODE_DOCKER_IMAGE' => 'node:fake-image'];
        yield ['NODE_VERSION' => '25.1', 'NODE_DOCKER_IMAGE' => 'node:fake-image'];
        yield ['DOCKER_ENV' => '--env "FOO=bar"'];
    }

    /**
     * @param null|array<string, string> $env
     *
     * @return array{
     *     analyze: list<string>,
     *     "build: dev": list<string>,
     *     "build: prod": list<string>,
     *     "docker compose: start app dev": list<string>,
     *     "docker compose: start app prod": list<string>,
     *     "docker compose: start app test": list<string>,
     *     "docker compose: start app": list<string>,
     *     "docker compose: stop app": list<string>,
     *     "shell: app": list<string>,
     *     "shell: node": list<string>,
     *     "test: unit": list<string>,
     *     "test: functional app": list<string>,
     *     "mkdir: npm": list<string>,
     *     "permissions: Node": list<string>,
     *     "clean: Node application": list<string>
     * }
     */
    private static function paths(?array $env): array
    {
        // defaults which are also defined in the Makefile
        $nodeVersion = $env['NODE_VERSION'] ?? '25.1';
        $nodeDockerImage = $env['NODE_DOCKER_IMAGE'] ?? 'node:%1$s-alpine';
        $dockerEnv = $env['DOCKER_ENV'] ?? ' ';

        return [
            'analyze' => [
                self::generateDockerComposeAppExecExecutionPath('npm run lint'),
            ],

            'build: dev' => [
                self::generateDockerBuildxExecutionPath('dev'),
            ],
            'build: prod' => [
                self::generateDockerBuildxExecutionPath('prod'),
            ],

            'docker compose: start app dev' => [
                self::generateDockerComposeAppExecutionPath('up --detach --remove-orphans --no-build', 'dev'),
            ],
            'docker compose: start app prod' => [
                self::generateDockerComposeAppExecutionPath('up --detach --remove-orphans --no-build', 'prod'),
            ],
            'docker compose: start app test' => [
                self::generateDockerComposeAppExecutionPath('up --detach --remove-orphans --no-build', 'test'),
            ],
            'docker compose: start app' => [
                self::generateDockerComposeAppExecutionPath('up --detach --remove-orphans --no-build'),
            ],
            'docker compose: stop app' => [
                self::generateDockerComposeAppExecutionPath('down --remove-orphans'),
            ],

            'permissions: Node' => self::generatePermissionsExecutionPath([
                'var/cache',
                'var/log',
            ]),

            'clean: Node application' => [
                'rm -rf var/cache/* var/log/*',
            ],

            'shell: app' => [
                self::generateDockerComposeAppExecExecutionPath('sh'),
            ],
            'shell: node' => [
                self::generateNodeExecutionPath('sh', nodeVersion: $nodeVersion, dockerImage: $nodeDockerImage, env: $dockerEnv),
            ],

            'test: unit' => [
                self::generateDockerComposeAppExecExecutionPath('npm test', 'test'),
            ],
            'test: functional app' => [
                self::generateDockerComposeAppExecExecutionPath('npm run test:e2e', 'test'),
            ],

            'mkdir: npm' => [
                'mkdir -p $HOME/.npm',
            ],
        ];
    }

    private static function generateNodeExecutionPath(string $command, string $nodeVersion, string $dockerImage, string $env): string
    {
        return self::normalize(\sprintf(
            'docker run --init --interactive  --rm %4$s%2$s --volume "$ROOT:$ROOT" --volume "$HOME/.npm:/home/node/.npm" --workdir "$ROOT" %3$s %1$s',
            \sprintf($command, $nodeVersion),
            self::generateDockerComposeExecutionUser(),
            \sprintf($dockerImage, $nodeVersion),
            $env
        ));
    }

    private static function generateDockerBuildxExecutionPath(string $env): string
    {
        return \sprintf('VERSION=latest docker buildx bake --load --file docker-compose.yaml --file .infra/docker-buildx/docker-buildx.%1$s.hcl', $env);
    }

    private static function generateDockerComposeAppExecutionPath(string $command, string $env = 'env'): string
    {
        return \sprintf('VERSION=latest docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.%2$s.yaml %1$s', $command, $env);
    }

    private static function generateDockerComposeAppExecExecutionPath(string $command, string $env = 'env'): string
    {
        return self::generateDockerComposeAppExecutionPath(\sprintf(
            'exec %2$s app %1$s',
            $command,
            self::generateDockerComposeExecutionUser()
        ), $env);
    }
}
