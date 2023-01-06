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

            'docker compose: start test' => [
                $this->generateDockerComposeExecutionPath('up --detach'),
            ],
            'docker compose: stop' => [
                $this->generateDockerComposeExecutionPath('down --remove-orphans'),
            ],

            'setup: test' => [
                $this->generateDockerComposeExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:drop --if-exists --force'),
                $this->generateDockerComposeExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:create'),
                $this->generateDockerComposeExecExecutionPath('vendor/bin/pimcore-install --env test --no-interaction --ignore-existing-config --skip-database-config'),
                $this->generateDockerComposeExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction sigwin:testing:setup'),
            ],

            'shell: app' => [
                $this->generateDockerComposeExecExecutionPath('bash'),
            ],
            'shell: PHP' => [
                $this->generatePhpqaExecutionPath('sh'),
            ],
            'test: unit' => [
                $this->generatePhpqaExecutionPath('php -d pcov.enabled=1 vendor/bin/phpunit --verbose --coverage-text --log-junit=var/phpqa/phpunit/junit.xml --coverage-xml var/phpqa/phpunit/coverage-xml/'),
                $this->generatePhpqaExecutionPath('infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/phpqa/phpunit/ --threads max'),
            ],
            'test: functional' => [
                $this->generateDockerComposeExecExecutionPath('vendor/bin/behat --strict'),
            ],
            'mkdir' => [
                'mkdir -p $HOME/.composer',
                'mkdir -p var/phpqa',
            ],
            'touch' => [
                'touch .env',
            ],
            'clean' => [
                'rm -rf var/ tests/runtime/var',
            ],
        ];
    }

    private function generatePhpqaExecutionPath(string $command, ?float $phpVersion = null): string
    {
        $phpVersion ??= 8.1;

        return $this->normalize(sprintf(
            'docker run --init --interactive  --rm --env "COMPOSER_CACHE_DIR=/composer/cache" %2$s --volume "$ROOT/var/phpqa:/cache" --volume "$ROOT:/project" --volume "$HOME/.composer:/composer" --workdir /project jakzal/phpqa:1.83.2-php%3$s-alpine %1$s',
            sprintf($command, $phpVersion),
            $this->generateDockerComposeExecutionUser(),
            $phpVersion
        ));
    }

    private function generateDockerComposeExecutionPath(string $command): string
    {
        return sprintf('COMPOSE_PROJECT_NAME=infra docker-compose --file tests/runtime/docker-compose.yaml %1$s', $command);
    }

    private function generateDockerComposeExecExecutionPath(string $command): string
    {
        return $this->generateDockerComposeExecutionPath(sprintf(
            'exec %2$s --env PIMCORE_KERNEL_CLASS=App\Kernel app %1$s',
            $command,
            $this->generateDockerComposeExecutionUser()
        ));
    }

    private function generateDockerComposeExecutionUser(): string
    {
        return \PHP_OS_FAMILY !== 'Windows' ? sprintf('--user "%1$s:%2$s"', getmyuid(), getmygid()) : '';
    }
}
