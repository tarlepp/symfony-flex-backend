<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/EntityReferenceExists.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class EntityReferenceExists
 *
 * Usage example;
 *  App\Validator\Constraints\EntityReferenceExists(entityClass=SomeClass::class)
 *
 * Just add that to your property as an annotation and you're good to go.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EntityReferenceExists extends Constraint
{
    public const ENTITY_REFERENCE_EXISTS_ERROR = '64888b5e-bded-449b-82ed-0cc1f73df14d';
    public const MESSAGE_SINGLE = 'Invalid id value "{{ id }}" given for entity "{{ entity }}".';
    public const MESSAGE_MULTIPLE = 'Invalid id values "{{ id }}" given for entity "{{ entity }}".';

    public string $entityClass = '';

    /**
     * {@inheritdoc}
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::ENTITY_REFERENCE_EXISTS_ERROR => 'ENTITY_REFERENCE_EXISTS_ERROR',
    ];

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
