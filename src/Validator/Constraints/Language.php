<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/Language.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Class Language
 *
 * Usage example;
 *  App\Validator\Constraints\Language()
 *
 * Just add that to your property as an annotation and you're good to go.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Language extends Constraint
{
    public const INVALID_LANGUAGE = '08bd61cf-ba27-45a3-9916-78c39253833a';
    public const MESSAGE = 'This language "{{ language }}" is not valid.';

    /**
     * {@inheritdoc}
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::INVALID_LANGUAGE => 'INVALID_LANGUAGE',
    ];

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
