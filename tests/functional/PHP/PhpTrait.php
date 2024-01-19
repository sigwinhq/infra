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
    protected static function getEnvs(): iterable
    {
        yield ['PHP_VERSION' => '8.1'];
        yield ['PHP_VERSION' => '8.2'];
        yield ['PHP_VERSION' => '8.3'];

        yield ['PHPQA_DOCKER_IMAGE' => 'fake/image:123'];
        yield ['PHP_VERSION' => '8.2', 'PHPQA_DOCKER_IMAGE' => 'fake/image:123'];
    }

    /**
     * @param null|array<string, string> $env
     *
     * @return array<string, list<string>>
     */
    private static function paths(?array $env): array
    {
        // defaults which are also defined in the Makefile
        $phpVersion = $env['PHP_VERSION'] ?? '8.3';
        $phpqaDockerImage = $env['PHPQA_DOCKER_IMAGE'] ?? 'jakzal/phpqa:1.95.1-php%1$s-alpine';

        return [
            'analyze' => [
                self::generatePhpqaExecutionPath('composer normalize --no-interaction --no-update-lock --dry-run', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
                self::generatePhpqaExecutionPath('php-cs-fixer fix --diff -vvv --dry-run', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
                self::generatePhpqaExecutionPath('phpstan analyse --configuration phpstan.neon.dist', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
                self::generatePhpqaExecutionPath('psalm --php-version=%1$s --config psalm.xml.dist', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
            ],
            'prepareAndAnalyze' => [
                self::generatePhpqaExecutionPath('composer normalize --no-interaction --no-update-lock', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
                self::generatePhpqaExecutionPath('php-cs-fixer fix --diff -vvv', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
                self::generatePhpqaExecutionPath('phpstan analyse --configuration phpstan.neon.dist', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
                self::generatePhpqaExecutionPath('psalm --php-version=%1$s --config psalm.xml.dist', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
            ],

            'box: build' => [
                self::generatePhpqaExecutionPath('box compile', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
            ],

            'build: dev' => [
                self::generateDockerBuildxExecutionPath('dev'),
            ],
            'build: prod' => [
                self::generateDockerBuildxExecutionPath('prod'),
            ],

            'composer: install' => [
                self::generatePhpqaExecutionPath('composer install --audit', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
            ],
            'composer: install-lowest' => [
                self::generatePhpqaExecutionPath('composer upgrade --prefer-lowest', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
            ],
            'composer: install-highest' => [
                self::generatePhpqaExecutionPath('composer upgrade', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
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
            'docker compose: start library test' => [
                self::generateDockerComposeTestExecutionPath('up --detach --remove-orphans --no-build'),
            ],
            'docker compose: stop Pimcore app' => [
                self::generateDockerComposeAppExecutionPath('down --remove-orphans'),
            ],
            'docker compose: stop Pimcore library' => [
                self::generateDockerComposeTestExecutionPath('down --remove-orphans'),
            ],

            'permissions: Pimcore' => self::generatePermissionsExecutionPath([
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
                self::generateDockerComposeAppExecExecutionPath('bin/console --env test --no-interaction doctrine:database:drop --if-exists --force', 'test'),
                self::generateDockerComposeAppExecExecutionPath('bin/console --env test --no-interaction doctrine:database:create', 'test'),
                self::generateDockerComposeAppExecExecutionPath('vendor/bin/pimcore-install --env test --no-interaction --skip-database-config', 'test'),
                self::generateDockerComposeAppExecExecutionPath('bin/console --env test --no-interaction sigwin:testing:setup', 'test'),
            ],
            'setup: Pimcore library test' => [
                self::generateDockerComposeTestExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:drop --if-exists --force'),
                self::generateDockerComposeTestExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:create'),
                self::generateDockerComposeTestExecExecutionPath('vendor/bin/pimcore-install --env test --no-interaction --ignore-existing-config --skip-database-config'),
                self::generateDockerComposeTestExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction sigwin:testing:setup'),
            ],

            'shell: app' => [
                self::generateDockerComposeAppExecExecutionPath('sh'),
            ],
            'shell: app library' => [
                self::generateDockerComposeTestExecExecutionPath('sh'),
            ],
            'shell: PHP' => [
                self::generatePhpqaExecutionPath('sh', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
            ],

            'test: unit' => [
                self::generatePhpqaExecutionPath('php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text --log-junit=var/phpqa/phpunit/junit.xml --coverage-xml var/phpqa/phpunit/coverage-xml/', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
                self::generatePhpqaExecutionPath('infection run --verbose --show-mutations --no-interaction --only-covered --only-covering-test-cases --coverage var/phpqa/phpunit/ --threads max', phpVersion: $phpVersion, dockerImage: $phpqaDockerImage),
            ],
            'test: functional app' => [
                self::generateDockerComposeAppExecExecutionPath('vendor/bin/behat --colors --strict', 'test'),
            ],
            'test: functional library' => [
                self::generateDockerComposeTestExecExecutionPath('vendor/bin/behat --colors --strict'),
            ],

            'mkdir: composer' => [
                'mkdir -p $HOME/.composer',
            ],
            'mkdir: phpqa' => [
                'mkdir -p $HOME/.composer',
                'mkdir -p var/phpqa',
            ],
            'touch: .env' => [
                'touch .env',
            ],
            'touch: composer.lock' => [
                'touch composer.lock',
            ],
            'clean: Pimcore application' => [
                'rm -rf var/admin/* var/cache/* var/log/* var/tmp/*',
            ],
            'clean: library' => [
                'rm -rf var/ tests/runtime/var',
            ],
        ];
    }

    private static function generatePhpqaExecutionPath(string $command, string $phpVersion, string $dockerImage): string
    {
        return self::normalize(sprintf(
            'docker run --init --interactive  --rm --env "COMPOSER_CACHE_DIR=/composer/cache" %2$s --volume "$ROOT/var/phpqa:/cache" --volume "$ROOT:/project" --volume "$HOME/.composer:/composer" --workdir /project %3$s %1$s',
            sprintf($command, $phpVersion),
            self::generateDockerComposeExecutionUser(),
            sprintf($dockerImage, $phpVersion)
        ));
    }

    private static function generateDockerBuildxExecutionPath(string $env): string
    {
        return sprintf('VERSION=latest docker buildx bake --load --file docker-compose.yaml --set *.args.BASE_URL=http://example.com/ --file .infra/docker-buildx/docker-buildx.%1$s.hcl', $env);
    }

    private static function generateDockerComposeAppExecutionPath(string $command, string $env = 'env'): string
    {
        return sprintf('VERSION=latest docker compose --file docker-compose.yaml --file .infra/docker-compose/docker-compose.%2$s.yaml %1$s', $command, $env);
    }

    private static function generateDockerComposeTestExecutionPath(string $command): string
    {
        return sprintf('COMPOSE_PROJECT_NAME=infra docker compose --file tests/runtime/docker-compose.yaml %1$s', $command);
    }

    private static function generateDockerComposeAppExecExecutionPath(string $command, string $env = 'env'): string
    {
        return self::generateDockerComposeAppExecutionPath(sprintf(
            'exec %2$s app %1$s',
            $command,
            self::generateDockerComposeExecutionUser()
        ), $env);
    }

    private static function generateDockerComposeTestExecExecutionPath(string $command): string
    {
        return self::generateDockerComposeTestExecutionPath(sprintf(
            'exec %2$s --env PIMCORE_KERNEL_CLASS=App\Kernel app %1$s',
            $command,
            self::generateDockerComposeExecutionUser()
        ));
    }
}
