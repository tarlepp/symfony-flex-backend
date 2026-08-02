<?php
declare(strict_types = 1);

/**
 * /src/Validator/Constraints/Timezone.php
 */

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Usage example;
 *  App\Validator\Constraints\Timezone()
 *
 * Just add that to your property as an annotation and you're good to go.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Timezone extends Constraint
{
    final public const string INVALID_TIMEZONE = '1f8dd2a3-5b61-43ca-a6b2-af553f86ac17';
    final public const string MESSAGE = 'This timezone "{{ timezone }}" is not valid.';

    /**
     * {@inheritDoc}
     *
     * @psalm-var array<string, string>
     */
    protected const array ERROR_NAMES = [
        self::INVALID_TIMEZONE => 'INVALID_TIMEZONE',
    ];
}
