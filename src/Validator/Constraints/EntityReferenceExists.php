<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/EntityReferenceExists.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class EntityReferenceExists
 *
 * @Annotation
 * @Target({"PROPERTY"})
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EntityReferenceExists extends Constraint
{
    /**
     * Unique constant for validator constrain
     */
    public const ENTITY_REFERENCE_EXISTS_ERROR = '64888b5e-bded-449b-82ed-0cc1f73df14d';

    /**
     * Message for validation error
     */
    public const MESSAGE_SINGLE = 'Invalid id value "{{ id }}" given for entity "{{ entity }}".';

    /**
     * Message for validation error
     */
    public const MESSAGE_MULTIPLE = 'Invalid id values "{{ id }}" given for entity "{{ entity }}".';

    public string $entityClass = '';

    /**
     * Error names configuration
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::ENTITY_REFERENCE_EXISTS_ERROR => 'ENTITY_REFERENCE_EXISTS_ERROR',
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
        return self::PROPERTY_CONSTRAINT;
    }
}
