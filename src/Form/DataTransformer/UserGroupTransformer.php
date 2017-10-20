<?php
declare(strict_types = 1);
/**
 * /src/Form/DataTransformer/UserGroupTransformer.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\DataTransformer;

use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class UserGroupTransformer
 *
 * @package App\Form\Console\DataTransformer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTransformer implements DataTransformerInterface
{
    /**
     * @var UserGroupResource
     */
    private $resource;

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
     * @param array|null $userGroups
     *
     * @return array
     */
    public function transform($userGroups): ?array
    {
        $output = [];

        if (\is_array($userGroups)) {
            /**
             * @param string|UserGroup $userGroup
             *
             * @return string
             */
            $iterator = function ($userGroup) {
                return \is_string($userGroup) ? $userGroup : $userGroup->getId();
            };

            $output = \array_map($iterator, $userGroups);
        }

        return $output;
    }

    /**
     * Transforms a string (Role id) to an object (Role).
     *
     * @param array $userGroups
     *
     * @return array|null
     *
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($userGroups): ?array
    {
        $output = null;

        if (\is_array($userGroups)) {
            $iterator = function (string $groupId) {
                $group = $this->resource->findOne($groupId);

                if ($group === null) {
                    throw new TransformationFailedException(\sprintf(
                        'User group with id "%s" does not exist!',
                        $groupId
                    ));
                }

                return $group;
            };

            $output = \array_map($iterator, $userGroups);
        }

        return $output;
    }
}
