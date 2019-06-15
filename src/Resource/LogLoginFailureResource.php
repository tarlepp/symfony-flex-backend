<?php
declare(strict_types = 1);
/**
 * /src/Resource/LogLoginFailureResource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Resource;

use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;
use App\Entity\LogLoginFailure as Entity;
use App\Entity\User;
use App\Repository\LogLoginFailureRepository as Repository;
use App\Rest\RestResource;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */
/**
 * Class LogLoginFailureResource
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
class LogLoginFailureResource extends RestResource
{
    /**
     * LogLoginFailureResource constructor.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->setRepository($repository);
    }

    /**
     * @param User $user
     */
    public function reset(User $user): void
    {
        $this->getRepository()->clear($user);
    }
}
