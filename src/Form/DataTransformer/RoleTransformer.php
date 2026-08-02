<?php
declare(strict_types = 1);

/**
 * /src/Form/DataTransformer/RoleTransformer.php
 */

namespace App\Form\DataTransformer;

use App\Entity\Role;
use App\Resource\RoleResource;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;
use function is_string;
use function sprintf;

/**
 * @implements DataTransformerInterface<Role|null, string>
 */
class RoleTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly RoleResource $resource,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Transforms an object (Role) to a string (Role id).
     */
    #[Override]
    public function transform(mixed $value): string
    {
        return $value instanceof Role ? $value->getId() : '';
    }

    /**
     * {@inheritDoc}
     *
     * Transforms a string (Role id) to an object (Role).
     *
     * @throws Throwable
     */
    #[Override]
    public function reverseTransform(mixed $value): ?Role
    {
        return is_string($value)
            ? $this->resource->findOne($value, false) ?? throw new TransformationFailedException(
                sprintf(
                    'Role with name "%s" does not exist!',
                    $value,
                ),
            )
            : null;
    }
}
