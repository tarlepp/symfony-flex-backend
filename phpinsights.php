<?php
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */
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
    'exclude' => [
        'bin',
        'build',
        'config',
        'doc',
        'docker',
        'public',
        'secrets',
        'src/Migrations',
        'templates',
        'tests',
        'translations',
        'var',
        'vendor',
        'vendor-bin',
    ],
    'add' => [
        //  ExampleMetric::class => [
        //      ExampleInsight::class,
        //  ]
    ],
    'remove' => [
        //  ExampleInsight::class,
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses::class,
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits::class,
        ObjectCalisthenics\Sniffs\Classes\ForbiddenPublicPropertySniff::class,
        ObjectCalisthenics\Sniffs\NamingConventions\NoSetterSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousTraitNamingSniff::class,
        SlevomatCodingStandard\Sniffs\Commenting\DocCommentSpacingSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\DisallowArrayTypeHintSyntaxSniff::class,
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
        ObjectCalisthenics\Sniffs\NamingConventions\ElementNameMinimalLengthSniff::class => [
            'allowedShortNames' => ['i', 'id', 'to', 'up', 'io', 'em'],
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
        PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterCastSniff::class => [
            'spacing' => 0,
        ],
        PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff::class => [
            'spacing' => 0,
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
