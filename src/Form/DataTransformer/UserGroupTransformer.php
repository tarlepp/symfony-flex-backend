<?php
declare(strict_types = 1);

/**
 * /src/Form/DataTransformer/UserGroupTransformer.php
 */

namespace App\Form\DataTransformer;

use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Override;
use Stringable;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;
use function array_map;
use function is_array;
use function sprintf;

/**
 * @implements DataTransformerInterface<array<array-key, UserGroup>|null, array<array-key, string>|null>
 */
class UserGroupTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly UserGroupResource $resource
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Transforms an array of objects (UserGroup) to an array of strings
     * (UserGroup id).
     */
    #[Override]
    public function transform(mixed $value): array
    {
        $callback = static fn (UserGroup|Stringable $userGroup): string => $userGroup instanceof UserGroup
            ? $userGroup->getId()
            : (string)$userGroup;

        return is_array($value) ? array_map($callback, $value) : [];
    }

    /**
     * {@inheritDoc}
     *
     * Transforms an array of strings (UserGroup id) to an array of objects
     * (UserGroup).
     *
     * @throws Throwable
     */
    #[Override]
    public function reverseTransform(mixed $value): ?array
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
