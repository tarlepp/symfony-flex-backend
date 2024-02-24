<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/Timezone.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Timezone extends Constraint
{
    final public const INVALID_TIMEZONE = '1f8dd2a3-5b61-43ca-a6b2-af553f86ac17';
    final public const MESSAGE = 'This timezone "{{ timezone }}" is not valid.';

    /**
     * {@inheritdoc}
     *
     * @psalm-var array<string, string>
     */
    protected const ERROR_NAMES = [
        self::INVALID_TIMEZONE => 'INVALID_TIMEZONE',
    ];
}
