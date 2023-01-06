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

namespace Sigwin\Infra\Test\Functional\Pimcore;

use Sigwin\Infra\Test\Functional\MakefileTestCase;
use Sigwin\Infra\Test\Functional\PHP\PhpTrait;

/**
 * @internal
 *
 * @coversNothing
 *
 * @small
 */
final class LibraryTest extends MakefileTestCase
{
    use PhpTrait;

    protected function getExpectedHelpCommandsExecutionPath(): array
    {
        $mkdirs = [
            'mkdir -p $HOME/.composer',
            'mkdir -p var/phpqa',
        ];

        $touches = [
            'touch .env',
        ];

        $cleans = [
            'rm -rf var/ tests/runtime/var',
        ];

        $analyze = [
            $this->generatePhpqaExecutionPath('composer normalize --no-interaction --no-update-lock --dry-run'),
            $this->generatePhpqaExecutionPath('php-cs-fixer fix --diff -vvv --dry-run'),
            $this->generatePhpqaExecutionPath('phpstan analyse --configuration phpstan.neon.dist'),
            $this->generatePhpqaExecutionPath('psalm --php-version=%1$s --config psalm.xml.dist'),
        ];

        $prepareAndAnalyze = [
            $this->generatePhpqaExecutionPath('composer normalize --no-interaction --no-update-lock'),
            $this->generatePhpqaExecutionPath('php-cs-fixer fix --diff -vvv'),
            $this->generatePhpqaExecutionPath('phpstan analyse --configuration phpstan.neon.dist'),
            $this->generatePhpqaExecutionPath('psalm --php-version=%1$s --config psalm.xml.dist'),
        ];

        $dockerComposeStartTest = [
            $this->generateDockerComposeExecutionPath('up --detach'),
        ];
        $dockerComposeStop = [
            $this->generateDockerComposeExecutionPath('down --remove-orphans'),
        ];

        $setupTest = [
            $this->generateDockerComposeExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:drop --if-exists --force'),
            $this->generateDockerComposeExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction doctrine:database:create'),
            $this->generateDockerComposeExecExecutionPath('vendor/bin/pimcore-install --env test --no-interaction --ignore-existing-config --skip-database-config'),
            $this->generateDockerComposeExecExecutionPath('php tests/runtime/bootstrap.php --env test --no-interaction sigwin:testing:setup'),
        ];

        $shellApp = [
            $this->generateDockerComposeExecExecutionPath('bash'),
        ];
        $shellPhp = [
            $this->generatePhpqaExecutionPath('sh'),
        ];

        $testUnit = [
            $this->generatePhpqaExecutionPath('php -d pcov.enabled=1 vendor/bin/phpunit --verbose --coverage-text --log-junit=var/phpqa/phpunit/junit.xml --coverage-xml var/phpqa/phpunit/coverage-xml/'),
            $this->generatePhpqaExecutionPath('infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/phpqa/phpunit/ --threads max'),
        ];

        $testFunctional = [
            $this->generateDockerComposeExecExecutionPath('vendor/bin/behat --strict'),
        ];

        return [
            'help' => [$this->generateHelpExecutionPath([
                __DIR__.'/../../../resources/Pimcore/library.mk',
                __DIR__.'/../../../resources/Pimcore/common.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge($mkdirs, $analyze),
            'dist' => array_merge($mkdirs, $prepareAndAnalyze, $testUnit, $testFunctional),
            'sh/php' => array_merge($mkdirs, $shellPhp),
            'test' => array_merge($mkdirs, $testUnit, $testFunctional),
            'clean' => $cleans,
            'setup/test' => array_merge($dockerComposeStartTest, $touches, $setupTest),
            'sh/app' => $shellApp,
            'start/test' => $dockerComposeStartTest,
            'stop' => $dockerComposeStop,
            'test/functional' => $testFunctional,
            'test/unit' => array_merge($mkdirs, $testUnit),
        ];
    }
}
