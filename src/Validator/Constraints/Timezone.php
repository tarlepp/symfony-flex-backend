<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/Timezone.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class Timezone
 *
 * @Annotation
 * @Target({"PROPERTY"})
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Timezone extends Constraint
{
    /**
     * Unique constant for validator constrain
     */
    public const INVALID_TIMEZONE = '1f8dd2a3-5b61-43ca-a6b2-af553f86ac17';

    /**
     * Message for validation error
     */
    public const MESSAGE = 'This timezone "{{ timezone }}" is not valid.';

    /**
     * Error names configuration
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::INVALID_TIMEZONE => 'INVALID_TIMEZONE',
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
