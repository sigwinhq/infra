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

namespace Sigwin\Infra\Test\Functional\PHP;

trait PhpTrait
{
    private function paths(): array
    {
        return [
            'analyze' => [
                $this->generatePhpqaExecutionPath('composer normalize --no-interaction --no-update-lock --dry-run'),
                $this->generatePhpqaExecutionPath('php-cs-fixer fix --diff -vvv --dry-run'),
                $this->generatePhpqaExecutionPath('phpstan analyse --configuration phpstan.neon.dist'),
                $this->generatePhpqaExecutionPath('psalm --php-version=%1$s --config psalm.xml.dist'),
            ],
            'prepareAndAnalyze' => [
                $this->generatePhpqaExecutionPath('composer normalize --no-interaction --no-update-lock'),
                $this->generatePhpqaExecutionPath('php-cs-fixer fix --diff -vvv'),
                $this->generatePhpqaExecutionPath('phpstan analyse --configuration phpstan.neon.dist'),
                $this->generatePhpqaExecutionPath('psalm --php-version=%1$s --config psalm.xml.dist'),
            ],

            'build: dev' => [
                $this->generateDockerBuildxExecutionPath('dev'),
            ],
            'build: prod' => [
                $this->generateDockerBuildxExecutionPath('prod'),
            ],

            'composer: install-lowest' => [
                $this->generatePhpqaExecutionPath('composer upgrade --prefer-lowest'),
            ],
            'composer: install-highest' => [
                $this->generatePhpqaExecutionPath('composer upgrade'),
            ],

            'docker compose: start app dev' => [
                $this->generateDockerComposeAppExecutionPath('up --detach --remove-orphans --no-build', 'dev'),
            ],
            'docker compose: start app prod' => [
                $this->generateDockerComposeAppExecutionPath('up --detach --remove-orphans --no-build', 'prod'),
            ],
            'docker compose: start app test' => [
                $this->generateDockerComposeAppExecutionPath('up --detach --remove-orphans --no-build', 'test'),
            ],
            'docker compose: start app' => [
                $this->generateDockerComposeAppExecutionPath('up --detach --remove-orphans --no-build'),
            ],
            'docker compose: start library test' => [
                $this->generateDockerComposeTestExecutionPath('up --detach --remove-orphans --no-build'),
            ],
            'docker compose: stop Pimcore app' => [
                $this->generateDockerComposeAppExecutionPath('down --remove-orphans'),
            ],
            'docker compose: stop Pimcore library' => [
                $this->generateDockerComposeTestExecutionPath('down --remove-orphans'),
            ],

            'permissions: Pimcore' => $this->generatePermissionsExecutionPath([
                'config/pimcore/classes',
                'public/var/assets',
                'public/var/tmp',
                'var/admin',
                'var/application-logger',
                'var/cache',
                'var/config',
                'var/email',
                'var/log',
                'var/tmp',
                'var/versions',
            ]),

            'setup: Pimcore app test' => [
                $this->generateDockerComposeAppExecExecutionPath('bin/console --env test --no-interaction doctrine:database:drop --if-exists --force', 'test'),
                $this->generateDockerComposeAppExecExecutionPath('bin/console --env test --no-interaction doctrine:database:create', 'test'),
                $this->generateDockerComposeAppExecExecutionPath('vendor/bin/pimcore-install --env test --no-interaction --ignore-existing-config --skip-database-config', 'test'),
                $this->generateDockerComposeAppExecExecutionPath('bin/console --env test --no-interaction sigwin:testing:setup', 'test'),
            ],
            'setup: Pimcore library test' => [
                $this->generateDockerComposeTestExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:drop --if-exists --force'),
                $this->generateDockerComposeTestExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:create'),
                $this->generateDockerComposeTestExecExecutionPath('vendor/bin/pimcore-install --env test --no-interaction --ignore-existing-config --skip-database-config'),
                $this->generateDockerComposeTestExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction sigwin:testing:setup'),
            ],

            'shell: app' => [
                $this->generateDockerComposeAppExecExecutionPath('sh'),
            ],
            'shell: app library' => [
                $this->generateDockerComposeTestExecExecutionPath('sh'),
            ],
            'shell: PHP' => [
                $this->generatePhpqaExecutionPath('sh'),
            ],

            'test: unit' => [
                $this->generatePhpqaExecutionPath('php -d pcov.enabled=1 vendor/bin/phpunit --verbose --coverage-text --log-junit=var/phpqa/phpunit/junit.xml --coverage-xml var/phpqa/phpunit/coverage-xml/'),
                $this->generatePhpqaExecutionPath('infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/phpqa/phpunit/ --threads max'),
            ],
            'test: functional app' => [
                $this->generateDockerComposeAppExecExecutionPath('vendor/bin/behat --colors --strict', 'test'),
            ],
            'test: functional library' => [
                $this->generateDockerComposeTestExecExecutionPath('vendor/bin/behat --colors --strict'),
            ],

            'mkdir: composer' => [
                'mkdir -p $HOME/.composer',
            ],
            'mkdir: phpqa' => [
                'mkdir -p $HOME/.composer',
                'mkdir -p var/phpqa',
            ],
            'touch' => [
                'touch .env',
            ],
            'clean: Pimcore application' => [
                'rm -rf var/admin/* var/cache/* var/log/* var/tmp/*',
            ],
            'clean: library' => [
                'rm -rf var/ tests/runtime/var',
            ],
        ];
    }

    private function generatePhpqaExecutionPath(string $command, ?float $phpVersion = null): string
    {
        $phpVersion ??= 8.2;

        return $this->normalize(sprintf(
            'docker run --init --interactive  --rm --env "COMPOSER_CACHE_DIR=/composer/cache" %2$s --volume "$ROOT/var/phpqa:/cache" --volume "$ROOT:/project" --volume "$HOME/.composer:/composer" --workdir /project jakzal/phpqa:1.86.1-php%3$s-alpine %1$s',
            sprintf($command, $phpVersion),
            $this->generateDockerComposeExecutionUser(),
            $phpVersion
        ));
    }

    private function generateDockerBuildxExecutionPath(string $env): string
    {
        return sprintf('VERSION=latest docker buildx bake --load --file docker-compose.yaml --set *.args.BASE_URL=http://example.com/ --file .infra/docker-buildx/docker-buildx.%1$s.hcl', $env);
    }

    private function generateDockerComposeAppExecutionPath(string $command, string $env = 'env'): string
    {
        return sprintf('VERSION=latest docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.%2$s.yaml %1$s', $command, $env);
    }

    private function generateDockerComposeTestExecutionPath(string $command): string
    {
        return sprintf('COMPOSE_PROJECT_NAME=infra docker compose --file tests/runtime/docker-compose.yaml %1$s', $command);
    }

    private function generateDockerComposeAppExecExecutionPath(string $command, string $env = 'env'): string
    {
        return $this->generateDockerComposeAppExecutionPath(sprintf(
            'exec %2$s app %1$s',
            $command,
            $this->generateDockerComposeExecutionUser()
        ), $env);
    }

    private function generateDockerComposeTestExecExecutionPath(string $command): string
    {
        return $this->generateDockerComposeTestExecutionPath(sprintf(
            'exec %2$s --env PIMCORE_KERNEL_CLASS=App\Kernel app %1$s',
            $command,
            $this->generateDockerComposeExecutionUser()
        ));
    }
}
