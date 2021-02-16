<?php
declare(strict_types = 1);
/**
 * /src/Form/DataTransformer/RoleTransformer.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Form\DataTransformer;

use App\Entity\Role;
use App\Resource\RoleResource;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;
use function sprintf;

/**
 * Class RoleTransformer
 *
 * @package App\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleTransformer implements DataTransformerInterface
{
    public function __construct(
        private RoleResource $resource,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Transforms an object (Role) to a string (Role id).
     */
    public function transform($value): string
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
    public function reverseTransform($value): ?Role
    {
        $role = null;

        if ($roleName !== null) {
            $role = $this->resource->findOne((string)$roleName, false);

            if ($role === null) {
                throw new TransformationFailedException(sprintf(
                    'Role with name "%s" does not exist!',
                    (string)$roleName
                ));
            }
        }

        return $role;
    }
}
