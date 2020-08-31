<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueEmail.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueEmail
 *
 * @Annotation
 * @Target({"CLASS"})
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueEmail extends Constraint
{
    /**
     * Unique constant for validator constrain
     */
    public const IS_UNIQUE_EMAIL_ERROR = 'd487278d-8b13-4da0-b4cc-f862e6e99af6';

    /**
     * Message for validation error
     */
    public const MESSAGE = 'This email is already taken.';

    /**
     * Error names configuration
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::IS_UNIQUE_EMAIL_ERROR => 'IS_UNIQUE_EMAIL_ERROR',
    ];

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Returns whether the constraint can be put onto classes, properties or both.
     *
     * This method should return one or more of the constants
     * Constraint::CLASS_CONSTRAINT and Constraint::PROPERTY_CONSTRAINT.
     *
     * @return string One or more constant values
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
