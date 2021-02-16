<?php
declare(strict_types = 1);
/**
 * /src/Form/DataTransformer/UserGroupTransformer.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Form\DataTransformer;

use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Stringable;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;
use function array_map;
use function array_values;
use function is_array;
use function sprintf;

/**
 * Class UserGroupTransformer
 *
 * @package App\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTransformer implements DataTransformerInterface
{
    private UserGroupResource $resource;

    public function __construct(UserGroupResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-return  array<int, string>
     */
    public function transform($value): array
    {
        $output = [];

        if (is_array($userGroups)) {
            $iterator = static fn ($group): string => $group instanceof UserGroup ? $group->getId() : (string)$group;

            $output = array_values(array_map('\strval', array_map($iterator, $userGroups)));
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int, UserGroup>|null
     *
     * @throws Throwable
     */
    public function reverseTransform($value): ?array
    {
        $output = null;

        if (is_array($value)) {
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

            $output = array_values(array_map($iterator, $value));
        }

        return $output;
    }
}
