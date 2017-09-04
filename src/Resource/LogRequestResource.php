<?php
declare(strict_types=1);
/**
 * /src/Resource/LogRequestResource.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Resource;

use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;
use App\Entity\LogRequest as Entity;
use App\Repository\LogRequestRepository as Repository;
use App\Rest\RestResource;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */
/**
 * Class LogRequestResource
 *
 * @package App\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method Repository  getRepository(): Repository
 * @method Entity[]    find(array $criteria = null, array $orderBy = null, int $limit = null, int $offset = null, array $search = null): array
 * @method Entity|null findOne(string $id, bool $throwExceptionIfNotFound = null): ?EntityInterface
 * @method Entity|null findOneBy(array $criteria, array $orderBy = null, bool $throwExceptionIfNotFound = null): ?EntityInterface
 * @method Entity      create(RestDtoInterface $dto, bool $skipValidation = null): EntityInterface
 * @method Entity      update(string $id, RestDtoInterface $dto, bool $skipValidation = null): EntityInterface
 * @method Entity      delete(string $id): EntityInterface
 * @method Entity      save(EntityInterface $entity, bool $skipValidation = null): EntityInterface
 */
class LogRequestResource extends RestResource
{
    /**
     * LogRequestResource constructor.
     *
     * @param Repository         $repository
     * @param ValidatorInterface $validator
     */
    public function __construct(Repository $repository, ValidatorInterface $validator)
    {
        $this->setRepository($repository);
        $this->setValidator($validator);
    }
}
