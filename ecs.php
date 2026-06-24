<?php
declare(strict_types=1);
/**
 * /ecs.php
 *
 * Configuration for `EasyCodingStandard` tool.
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

use PhpCsFixer\Fixer\ArrayNotation\NoMultilineWhitespaceAroundDoubleArrowFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSummaryFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(psr12: true, common: true, cleanCode: true)
    ->withConfiguredRule(
        IncrementStyleFixer::class,
        [
            'style' => 'post',
        ],
    )
    ->withConfiguredRule(
        CastSpacesFixer::class,
        [
            'space' => 'none',
        ],
    )
    ->withConfiguredRule(
        YodaStyleFixer::class,
        [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    )
    ->withConfiguredRule(
        ConcatSpaceFixer::class,
        [
            'spacing' => 'one',
        ],
    )
    ->withConfiguredRule(
        OrderedImportsFixer::class,
        [
            'imports_order' => ['class', 'function', 'const'],
        ],
    )
    ->withConfiguredRule(
        NoSuperfluousPhpdocTagsFixer::class,
        [
            'remove_inheritdoc' => false,
            'allow_mixed' => true,
            'allow_unused_params' => false,
        ],
    )
    ->withConfiguredRule(
        DeclareEqualNormalizeFixer::class,
        [
            'space' => 'single',
        ],
    )
    ->withConfiguredRule(
        BlankLineBeforeStatementFixer::class,
        [
            'statements' => ['continue', 'declare', 'return', 'throw', 'try'],
        ],
    )
    ->withConfiguredRule(
        BinaryOperatorSpacesFixer::class,
        [
            'operators' => [
                '&' => 'align',
            ],
        ],
    )
    ->withSkip([
        BlankLineAfterOpeningTagFixer::class => null,
        ClassAttributesSeparationFixer::class => null,
        NotOperatorWithSuccessorSpaceFixer::class => null,
    ]);
