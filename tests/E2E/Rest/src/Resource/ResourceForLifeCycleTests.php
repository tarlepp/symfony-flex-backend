<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/src/Resource/ResourceForLifeCycleTests.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest\src\Resource;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Role as Entity;
use App\Repository\RoleRepository as Repository;
use App\Rest\RestResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Class ResourceForLifeCycleTests
 *
 * @package App\Tests\E2E\Rest\src\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Repository getRepository(): Repository
 * @method Entity[] find(array $criteria = null, array $orderBy = null, int $limit = null, int $offset = null, array $search = null): array
 * @method Entity|null findOne(string $id, bool $throwExceptionIfNotFound = null): ?EntityInterface
 * @method Entity|null findOneBy(array $criteria, array $orderBy = null, bool $throwExceptionIfNotFound = null): ?EntityInterface
 * @method Entity create(RestDtoInterface $dto, bool $skipValidation = null): EntityInterface
 * @method Entity update(string $id, RestDtoInterface $dto, bool $skipValidation = null): EntityInterface
 * @method Entity delete(string $id): EntityInterface
 * @method Entity save(EntityInterface $entity, bool $skipValidation = null): EntityInterface
 *
 * @codingStandardsIgnoreEnd
 */
class ResourceForLifeCycleTests extends RestResource
{
    /**
     * Class constructor.
     */
    public function __construct(Repository $repository)
    {
        $this->setRepository($repository);
    }

    /**
     * After lifecycle method for findOne method.
     *
     * Notes: If you make changes to entity in this lifecycle method by default it will be saved on end of current
     *          request. To prevent this you need to detach current entity from entity manager.
     *
     *          Also note that if you've made some changes to entity and you eg. throw an exception within this method
     *          your entity will be saved if it has eg Blameable / Timestampable traits attached.
     *
     * @param EntityInterface|Entity|null $entity
     *
     * @throws Throwable
     */
    public function afterFindOne(string &$id, ?EntityInterface $entity = null): void
    {
        parent::afterFindOne($id, $entity);

        $entity->setDescription('some description');

        throw new HttpException(418, 'this should not trigger entity flush to database');
    }
}
