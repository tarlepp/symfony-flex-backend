<?php
declare(strict_types = 1);
return [
    /*
    |--------------------------------------------------------------------------
    | Default Preset
    |--------------------------------------------------------------------------
    |
    | This option controls the default preset that will be used by PHP Insights
    | to make your code reliable, simple, and clean. However, you can always
    | adjust the `Metrics` and `Insights` below in this configuration file.
    |
    | Supported: "default", "laravel", "symfony"
    |
    */
    'preset' => 'symfony',
    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may adjust all the various `Insights` that will be used by PHP
    | Insights. You can either add, remove or configure `Insights`. Keep in
    | mind, that all added `Insights` must belong to a specific `Metric`.
    |
    */
    'add' => [
        //  ExampleMetric::class => [
        //      ExampleInsight::class,
        //  ]
    ],
    'remove' => [
        //  ExampleInsight::class,
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses::class,
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits::class,
        ObjectCalisthenics\Sniffs\NamingConventions\ElementNameMinimalLengthSniff::class,
        ObjectCalisthenics\Sniffs\Classes\ForbiddenPublicPropertySniff::class,
        ObjectCalisthenics\Sniffs\NamingConventions\NoSetterSniff::class,
        PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterCastSniff::class,
        PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff::class,
        SlevomatCodingStandard\Sniffs\Commenting\DocCommentSpacingSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousTraitNamingSniff::class,
    ],
    'config' => [
        //  ExampleInsight::class => [
        //      'key' => 'value',
        //  ],
        ObjectCalisthenics\Sniffs\Files\ClassTraitAndInterfaceLengthSniff::class => [
            'maxLength' => 600,
        ],
        ObjectCalisthenics\Sniffs\Files\FunctionLengthSniff::class => [
            'maxLength' => 45,
        ],
        ObjectCalisthenics\Sniffs\Metrics\MaxNestingLevelSniff::class => [
            'maxNestingLevel' => 3,
        ],
        ObjectCalisthenics\Sniffs\Metrics\MethodPerClassLimitSniff::class => [
            'maxCount' => 25,
        ],
        ObjectCalisthenics\Sniffs\Metrics\PropertyPerClassLimitSniff::class => [
            'maxCount' => 20,
        ],
        PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 140,
            'ignoreComments' => true,
        ],
        SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff::class => [
            'searchAnnotations' => true,
        ],
        SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff::class => [
            'newlinesCountAfterDeclare' => 1,
            'newlinesCountBetweenOpenTagAndDeclare' => 1,
            'spacesCountAroundEqualsSign' => 1,
        ],
    ],
];
