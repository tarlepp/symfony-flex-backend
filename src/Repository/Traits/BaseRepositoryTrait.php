<?php
declare(strict_types=1);
/**
 * /src/Repository/Traits/BaseRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository\Traits;

use App\Entity\EntityInterface;
use App\Repository\BaseRepositoryInterface;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Trait BaseRepository
 *
 * @package App\Repository\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method EntityManager getEntityManager()
 * @method string        getEntityName()
 */
trait BaseRepositoryTrait
{
    /**
     * Gets a reference to the entity identified by the given type and identifier without actually loading it,
     * if the entity is not yet loaded.
     *
     * @param string $id
     *
     * @return Proxy|null
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getReference(string $id): ?Proxy
    {
        return $this->getEntityManager()->getReference($this->getEntityName(), $id);
    }

    /**
     * Gets all association mappings of the class.
     *
     * @return array
     */
    public function getAssociations(): array
    {
        return $this->getEntityManager()->getClassMetadata($this->getEntityName())->getAssociationMappings();
    }

    /**
     * Method to create new query builder for current entity.
     *
     * @param string $alias
     * @param string $indexBy
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder(string $alias = null, string $indexBy = null): QueryBuilder
    {
        $alias = $alias ?? 'entity';

        // Create new query builder
        $queryBuilder = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select($alias)
            ->from($this->getEntityName(), $alias, $indexBy);

        return $queryBuilder;
    }

    /**
     * Helper method to persist specified entity to database.
     *
     * @param EntityInterface $entity
     *
     * @return BaseRepositoryInterface
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function save(EntityInterface $entity): BaseRepositoryInterface
    {
        // Persist on database
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;
    }

    /**
     * Helper method to remove specified entity from database.
     *
     * @param EntityInterface $entity
     *
     * @return BaseRepositoryInterface
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function remove(EntityInterface $entity): BaseRepositoryInterface
    {
        // Remove from database
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;
    }
}
