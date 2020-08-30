<?php
declare(strict_types = 1);
/**
 * /src/Resource/LogLoginResource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Resource;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogLogin as Entity;
use App\Repository\LogLoginRepository as Repository;
use App\Rest\RestResource;

/**
 * Class LogLoginResource
 *
 * @package App\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity getReference(string $id)
 * @method Repository getRepository()
 * @method array<int, Entity> find(?array $criteria = null, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null)
 * @method Entity|null findOne(string $id, ?bool $throwExceptionIfNotFound = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null, ?bool $throwExceptionIfNotFound = null)
 * @method Entity create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null)
 * @method Entity update(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null)
 * @method Entity patch(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null)
 * @method Entity delete(string $id, ?bool $flush = null)
 * @method Entity save(EntityInterface $entity, ?bool $flush = null, ?bool $skipValidation = null)
 *
 * @codingStandardsIgnoreEnd
 */
class LogLoginResource extends RestResource
{
    /**
     * LogLoginFailureResource constructor.
     */
    public function __construct(Repository $repository)
    {
        $this->setRepository($repository);
    }
}
