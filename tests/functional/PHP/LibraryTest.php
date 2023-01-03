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

use Sigwin\Infra\Test\Functional\MakefileTestCase;

/**
 * @internal
 *
 * @coversNothing
 *
 * @small
 */
final class LibraryTest extends MakefileTestCase
{
    protected function getExpectedHelpCommandsExecutionPath(): array
    {
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

        $shell = [
            $this->generatePhpqaExecutionPath('sh'),
        ];

        $test = [
            $this->generatePhpqaExecutionPath('php -d pcov.enabled=1 vendor/bin/phpunit --verbose --coverage-text --log-junit=var/phpqa/phpunit/junit.xml --coverage-xml var/phpqa/phpunit/coverage-xml/'),
            $this->generatePhpqaExecutionPath('infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/phpqa/phpunit/ --threads max'),
        ];

        return [
            'help' => [$this->generateHelpExecutionPath([
                __DIR__.'/../../../resources/PHP/library.mk',
                __DIR__.'/../../../resources/PHP/common.mk',
            ])],
            'analyze' => array_merge(['mkdir -p $HOME/.composer', 'mkdir -p var/phpqa'], $analyze),
            'dist' => array_merge(['mkdir -p $HOME/.composer', 'mkdir -p var/phpqa'], $prepareAndAnalyze, $test),
            'sh/php' => array_merge(['mkdir -p $HOME/.composer', 'mkdir -p var/phpqa'], $shell),
            'test' => array_merge(['mkdir -p $HOME/.composer', 'mkdir -p var/phpqa'], $test),
        ];
    }
}
