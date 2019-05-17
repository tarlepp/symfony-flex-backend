<?php
declare(strict_types = 1);
/**
 * /src/Form/DataTransformer/RoleTransformer.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Form\DataTransformer;

use App\Entity\Role;
use App\Resource\RoleResource;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class RoleTransformer
 *
 * @package App\Form\Console\DataTransformer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleTransformer implements DataTransformerInterface
{
    /**
     * @var RoleResource
     */
    private $resource;

    /**
     * RoleTransformer constructor.
     *
     * @param RoleResource $resource
     */
    public function __construct(RoleResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transforms an object (Role) to a string (Role id).
     *
     * @param Role|mixed|null $role
     *
     * @return string
     */
    public function transform($role): string
    {
        return $role instanceof Role ? $role->getId() : '';
    }

    /**
     * Transforms a string (Role id) to an object (Role).
     *
     * @param string|mixed|null $roleName
     *
     * @return Role|null
     *
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($roleName): ?Role
    {
        if ($roleName !== null) {
            $role = $this->resource->findOne($roleName);

            if ($role === null) {
                throw new TransformationFailedException(\sprintf(
                    'Role with name "%s" does not exist!',
                    $roleName
                ));
            }
        }

        return $role ?? null;
    }
}
