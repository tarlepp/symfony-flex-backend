<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/Locale.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class Locale
 *
 * @Annotation
 * @Target({"PROPERTY"})
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Locale extends Constraint
{
    /**
     * Unique constant for validator constrain
     */
    public const INVALID_LOCALE = '44e3862f-2d38-46d4-b1ae-632990814af6';

    /**
     * Message for validation error
     */
    public const MESSAGE = 'This locale "{{ locale }}" is not valid.';

    /**
     * Error names configuration
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::INVALID_LOCALE => 'INVALID_LOCALE',
    ];

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritdoc}
     */
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
