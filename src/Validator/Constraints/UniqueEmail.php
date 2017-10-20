<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueEmail.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueEmail extends Constraint
{
    // Unique constant for validator constrain
    const IS_UNIQUE_EMAIL_ERROR = 'd487278d-8b13-4da0-b4cc-f862e6e99af6';

    // Error names configuration
    protected static $errorNames = [
        self::IS_UNIQUE_EMAIL_ERROR => 'IS_UNIQUE_EMAIL_ERROR'
    ];

    // Message for validation error
    public $message = 'This email is already taken.';

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Returns whether the constraint can be put onto classes, properties or both.
     *
     * This method should return one or more of the constants
     * Constraint::CLASS_CONSTRAINT and Constraint::PROPERTY_CONSTRAINT.
     *
     * @return string|array One or more constant values
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
