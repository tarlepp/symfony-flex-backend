<?php
declare(strict_types = 1);
/**
 * /src/Resource/RoleResource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Resource;

use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;
use App\Entity\Role as Entity;
use App\Repository\RoleRepository as Repository;
use App\Rest\RestResource;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */
/**
 * Class RoleResource
 *
 * @package App\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity      getReference(string $id): Entity
 * @method Repository  getRepository(): Repository
 * @method Entity[]    find(?array $criteria = null, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null): array
 * @method Entity|null findOne(string $id, ?bool $throwExceptionIfNotFound = null): ?EntityInterface
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null, ?bool $throwExceptionIfNotFound = null): ?EntityInterface
 * @method Entity      create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null): EntityInterface
 * @method Entity      update(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null): EntityInterface
 * @method Entity      delete(string $id, ?bool $flush = null): EntityInterface
 * @method Entity      save(EntityInterface $entity, ?bool $flush = null, ?bool $skipValidation = null): EntityInterface
 *
 * @codingStandardsIgnoreEnd
 */
class RoleResource extends RestResource
{
    /**
     * Class constructor.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->setRepository($repository);
    }
}
