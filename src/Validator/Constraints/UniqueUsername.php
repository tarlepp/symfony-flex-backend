<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueUsername.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueUsername
 *
 * @Annotation
 * @Target({"CLASS"})
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueUsername extends Constraint
{
    /**
     * Unique constant for validator constrain
     */
    public const IS_UNIQUE_USERNAME_ERROR = 'ea62740a-4d9b-4a25-9a56-46fb4c3d5fea';

    /**
     * Message for validation error
     */
    public const MESSAGE = 'This username is already taken.';

    /**
     * Error names configuration
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::IS_UNIQUE_USERNAME_ERROR => 'IS_UNIQUE_USERNAME_ERROR',
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
