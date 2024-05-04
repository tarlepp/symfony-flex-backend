<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/Locale.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Usage example;
 *  App\Validator\Constraints\Locale()
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
class Locale extends Constraint
{
    final public const string INVALID_LOCALE = '44e3862f-2d38-46d4-b1ae-632990814af6';
    final public const string MESSAGE = 'This locale "{{ locale }}" is not valid.';

    /**
     * {@inheritdoc}
     *
     * @psalm-var array<string, string>
     */
    protected const array ERROR_NAMES = [
        self::INVALID_LOCALE => 'INVALID_LOCALE',
    ];
}
