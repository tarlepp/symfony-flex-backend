<?php
declare(strict_types = 1);
/**
 * /src/Repository/Traits/RepositoryWrappersTrait.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository\Traits;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Class RepositoryWrappersTrait
 *
 * @package App\Repository\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method string getEntityName(): string
 */
trait RepositoryWrappersTrait
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /** @noinspection GenericObjectTypeUsageInspection */
    /**
     * Gets a reference to the entity identified by the given type and identifier without actually loading it,
     * if the entity is not yet loaded.
     *
     * @param string $id
     *
     * @return Proxy|object|null
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getReference(string $id)
    {
        return $this->getEntityManager()->getReference($this->getEntityName(), $id);
    }

    /**
     * Gets all association mappings of the class.
     *
     * @return string[]|array<int, string>
     */
    public function getAssociations(): array
    {
        return $this->getClassMetaData()->getAssociationMappings();
    }

    /**
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata|\Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetaData()
    {
        return $this->getEntityManager()->getClassMetadata($this->getEntityName());
    }

    /**
     * Getter method for EntityManager for current entity.
     *
     * @return ObjectManager|EntityManager
     */
    public function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass($this->getEntityName());
    }

    /**
     * Method to create new query builder for current entity.
     *
     * @param string|null $alias
     * @param string|null $indexBy
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder(?string $alias = null, ?string $indexBy = null): QueryBuilder
    {
        $alias = $alias ?? 'entity';

        // Create new query builder
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select($alias)
            ->from($this->getEntityName(), $alias, $indexBy);
    }
}
