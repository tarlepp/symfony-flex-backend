<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/Locale.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class Locale
 *
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
class Locale extends Constraint
{
    public const INVALID_LOCALE = '44e3862f-2d38-46d4-b1ae-632990814af6';
    public const MESSAGE = 'This locale "{{ locale }}" is not valid.';

    /**
     * {@inheritdoc}
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::INVALID_LOCALE => 'INVALID_LOCALE',
    ];

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
