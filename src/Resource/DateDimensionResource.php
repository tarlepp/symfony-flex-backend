<?php
declare(strict_types = 1);
/**
 * /src/Resource/DateDimensionResource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Resource;

use App\DTO\RestDtoInterface;
use App\Entity\DateDimension as Entity;
use App\Entity\EntityInterface;
use App\Repository\DateDimensionRepository as Repository;
use App\Rest\RestResource;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */
/**
 * Class DateDimensionResource
 *
 * @package App\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
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
class DateDimensionResource extends RestResource
{
    /**
     * DateDimensionResource constructor.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->setRepository($repository);
    }
}
