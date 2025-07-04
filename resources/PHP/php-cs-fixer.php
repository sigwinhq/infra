<?php

declare(strict_types=1);

/*
 * This file is part of the yassg project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use PhpCsFixer\Config;

return static function (string $root, ?string $header = null): Config {
    $finder = PhpCsFixer\Finder::create()
        ->exclude('var')
        ->exclude('vendor')
        ->in($root);

    return (new Config('sigwin/infra'))
        ->setCacheFile($root.'/var/phpqa/php-cs-fixer.cache')
        ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
        ->setUnsupportedPhpVersionAllowed(true)
        ->setRiskyAllowed(true)
        ->setRules(
            [
                '@DoctrineAnnotation' => true,
                '@PHP70Migration' => true,
                '@PHP70Migration:risky' => true,
                '@PHP71Migration' => true,
                '@PHP71Migration:risky' => true,
                '@PHP73Migration' => true,
                '@PHP74Migration' => true,
                '@PHP74Migration:risky' => true,
                '@PHP80Migration' => true,
                '@PHP80Migration:risky' => true,
                '@PhpCsFixer' => true,
                '@PhpCsFixer:risky' => true,
                '@Symfony' => true,
                '@Symfony:risky' => true,
                'attribute_empty_parentheses' => true,
                'header_comment' => ['header' => $header],
                'date_time_immutable' => true,
                'final_class' => true,
                'general_phpdoc_annotation_remove' => ['annotations' => ['@author']],
                'mb_str_functions' => true,
                'method_chaining_indentation' => false,
                'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
                'not_operator_with_successor_space' => true,
                'nullable_type_declaration_for_default_null_value' => true,
                'ordered_interfaces' => true,
                'php_unit_attributes' => true,
                'php_unit_internal_class' => true,
                'php_unit_size_class' => true,
                'phpdoc_order_by_value' => ['annotations' => [
                    'covers',
                    'coversNothing',
                    'dataProvider',
                    'depends',
                    'group',
                    'internal',
                    'method',
                    'mixin',
                    'property',
                    'property-read',
                    'property-write',
                    'requires',
                    'throws',
                    'uses',
                ]],
                'phpdoc_to_comment' => ['ignored_tags' => ['phpstan-ignore-next-line', 'psalm-suppress']],
                'phpdoc_to_return_type' => true,
                'phpdoc_types_order' => [
                    'null_adjustment' => 'always_first',
                ],
                'return_assignment' => false,
                'static_lambda' => true,
                'use_arrow_functions' => false,
                'yoda_style' => [
                    'always_move_variable' => true,
                    'equal' => false,
                    'identical' => false,
                    'less_and_greater' => false,
                ],
            ]
        )
        ->setFinder($finder);
};
