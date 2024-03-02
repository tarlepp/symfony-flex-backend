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
use function is_string;
use function sprintf;

/**
 * @psalm-suppress MissingTemplateParam
 *
 * @package App\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly RoleResource $resource,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * Transforms an object (Role) to a string (Role id).
     *
     * @psalm-param Role|mixed $value
     */
    public function transform(mixed $value): string
    {
        return $value instanceof Role ? $value->getId() : '';
    }

    /**
     * {@inheritdoc}
     *
     * Transforms a string (Role id) to an object (Role).
     *
     * @phpstan-param mixed $value
     *
     * @throws Throwable
     */
    public function reverseTransform(mixed $value): ?Role
    {
        return is_string($value)
            ? $this->resource->findOne($value, false) ?? throw new TransformationFailedException(
                sprintf(
                    'Role with name "%s" does not exist!',
                    $value
                ),
            )
            : null;
    }
}
