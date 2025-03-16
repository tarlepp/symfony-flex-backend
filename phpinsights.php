<?php
declare(strict_types = 1);
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */

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
        'migrations',
        'templates',
        'tests',
        'translations',
        'var',
        'vendor',
        'tools',
        'rector.php',
    ],
    'add' => [
        //  ExampleMetric::class => [
        //      ExampleInsight::class,
        //  ]
    ],
    'remove' => [
        //  ExampleInsight::class,
        NunoMaduro\PhpInsights\Domain\Insights\Composer\ComposerMustBeValid::class,
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses::class,
        // Disabled for now because of the - https://github.com/nunomaduro/phpinsights/issues/704
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenSecurityIssues::class,
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits::class,
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff::class,
        ObjectCalisthenics\Sniffs\Classes\ForbiddenPublicPropertySniff::class,
        ObjectCalisthenics\Sniffs\NamingConventions\NoSetterSniff::class,
        PhpCsFixer\Fixer\ArrayNotation\NoMultilineWhitespaceAroundDoubleArrowFixer::class,
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousTraitNamingSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\ForbiddenPublicPropertySniff::class,
        SlevomatCodingStandard\Sniffs\Commenting\DocCommentSpacingSniff::class,
        SlevomatCodingStandard\Sniffs\Commenting\InlineDocCommentDeclarationSniff::class,
        SlevomatCodingStandard\Sniffs\Commenting\UselessInheritDocCommentSniff ::class,
        SlevomatCodingStandard\Sniffs\Commenting\UselessFunctionDocCommentSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\DisallowArrayTypeHintSyntaxSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff::class,
        Symplify\CodingStandard\Fixer\Strict\BlankLineAfterStrictTypesFixer::class,
    ],
    'config' => [
        //  ExampleInsight::class => [
        //      'key' => 'value',
        //  ],
        SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff::class => [
            'maxLinesLength' => 50,
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
        PhpCsFixer\Fixer\CastNotation\CastSpacesFixer::class => [
            'space' => 'none', // possible values ['single', 'none'],
        ],
        PhpCsFixer\Fixer\Import\OrderedImportsFixer::class => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha', // possible values ['alpha', 'length', 'none']
        ],
        PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer::class => [
            'space' => 'single', // possible values ['none', 'single']
        ],
        PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer::class => [
            'operators' => [
                '&' => 'align',
            ],
        ],
        SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff::class => [
            'exclude' => [
                'src/ArgumentResolver/LoggedInUserValueResolver.php',
                'src/ArgumentResolver/RestDtoValueResolver.php',
                'src/AutoMapper/RestRequestMapper.php',
                'src/Decorator/StopwatchDecorator.php',
                'src/Doctrine/DBAL/Types/EnumType.php',
                'src/Rest/Traits/Methods/RestMethodProcessCriteria.php',
                'src/Rest/Traits/RestResourceCount.php',
                'src/Rest/Traits/RestResourceCreate.php',
                'src/Rest/Traits/RestResourceDelete.php',
                'src/Rest/Traits/RestResourceFind.php',
                'src/Rest/Traits/RestResourceFindOne.php',
                'src/Rest/Traits/RestResourceFindOneBy.php',
                'src/Rest/Traits/RestResourceIds.php',
                'src/Rest/Traits/RestResourcePatch.php',
                'src/Rest/Traits/RestResourceSave.php',
                'src/Rest/Traits/RestResourceUpdate.php',
                'src/Security/Authenticator/ApiKeyAuthenticator.php',
                'src/Security/Handler/TranslatedAuthenticationFailureHandler.php',
                'src/Security/Provider/ApiKeyUserProvider.php',
                'src/Security/Voter/IsUserHimselfVoter.php',
                'src/Serializer/Normalizer/CollectionNormalizer.php',
                'src/Validator/Constraints/LanguageValidator.php',
                'src/Validator/Constraints/LocaleValidator.php',
                'src/Validator/Constraints/TimezoneValidator.php',
                'src/Validator/Constraints/UniqueEmailValidator.php',
                'src/Validator/Constraints/UniqueUsernameValidator.php',
            ],
        ],
        SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff::class => [
            'searchAnnotations' => true,
        ],
        SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff::class => [
            'linesCountBeforeDeclare' => 0,
            'linesCountAfterDeclare' => 0,
            'spacesCountAroundEqualsSign' => 1,
        ],
    ],
];
