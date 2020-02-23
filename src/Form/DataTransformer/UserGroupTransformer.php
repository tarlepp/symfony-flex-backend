<?php
declare(strict_types = 1);
/**
 * /src/Form/DataTransformer/UserGroupTransformer.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Form\DataTransformer;

use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use function array_map;
use function array_values;
use function is_array;
use function sprintf;

/**
 * Class UserGroupTransformer
 *
 * @package App\Form\Console\DataTransformer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTransformer implements DataTransformerInterface
{
    private UserGroupResource $resource;

    /**
     * UserGroupTransformer constructor.
     *
     * @param UserGroupResource $resource
     */
    public function __construct(UserGroupResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transforms an object (Role) to a string (Role id).
     *
     * @param string[]|UserGroup[]|mixed|null $userGroups
     *
     * @return string[]
     */
    public function transform($userGroups): ?array
    {
        $output = [];

        if (is_array($userGroups)) {
            $iterator =
                /**
                 * @param string|UserGroup $userGroup
                 *
                 * @return string
                 */
                static fn ($userGroup): string => $userGroup instanceof UserGroup ? $userGroup->getId() : $userGroup;

            $output = array_values(array_map('\strval', array_map($iterator, $userGroups)));
        }

        return $output;
    }

    /**
     * Transforms a string (Role id) to an object (Role).
     *
     * @param string[]|mixed $userGroups
     *
     * @return UserGroup[]|null
     *
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($userGroups): ?array
    {
        $output = null;

        if (is_array($userGroups)) {
            $iterator = function (string $groupId): UserGroup {
                /** @var UserGroup|null $group */
                $group = $this->resource->findOne($groupId);

                if ($group === null) {
                    throw new TransformationFailedException(sprintf(
                        'User group with id "%s" does not exist!',
                        $groupId
                    ));
                }

                return $group;
            };

            $output = array_values(array_map($iterator, $userGroups));
        }

        return $output;
    }
}
