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
     * Transforms an array of objects (UserGroup) to an array of strings
     * (UserGroup id).
     *
     * @psalm-param array<int, string|UserGroup>|mixed $value
     * @psalm-return array<int, string>
     */
    public function transform($value): array
    {
        return is_array($value)
            ? array_map(
                static fn (UserGroup | Stringable $userGroup): string =>
                    $userGroup instanceof UserGroup ? $userGroup->getId() : (string)$userGroup,
                $value,
            )
            : [];
    }

    /**
     * {@inheritdoc}
     *
     * Transforms an array of strings (UserGroup id) to an array of objects
     * (UserGroup).
     *
     * @psalm-param array<int, string>|mixed $value
     * @psalm-return array<int, UserGroup>|null
     *
     * @throws Throwable
     */
    public function reverseTransform($value): ?array
    {
        return is_array($value)
            ? array_map(
                fn (string $groupId): UserGroup => $this->resource->findOne($groupId, false) ??
                    throw new TransformationFailedException(
                        sprintf('User group with id "%s" does not exist!', $groupId),
                    ),
                $value,
            )
            : null;
    }
}
