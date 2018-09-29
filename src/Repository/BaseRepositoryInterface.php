<?php
declare(strict_types = 1);
/**
 * /src/Repository/BaseRepositoryInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\EntityInterface;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Interface BaseRepositoryInterface
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface BaseRepositoryInterface
{
    /**
     * Getter method for entity name.
     *
     * @return string
     */
    public function getEntityName(): string;

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
    public function getReference(string $id);

    /**
     * Gets all association mappings of the class.
     *
     * @return string[]|array<int, string>
     */
    public function getAssociations(): array;

    /**
     * Getter method for search columns of current entity.
     *
     * @return string[]
     */
    public function getSearchColumns(): array;

    /**
     * Getter method for EntityManager for current entity.
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager;

    /**
     * Method to create new query builder for current entity.
     *
     * @param string|null $alias
     * @param string|null $indexBy
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder(?string $alias = null, ?string $indexBy = null): QueryBuilder;

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
    public function save(EntityInterface $entity): self;

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
    public function remove(EntityInterface $entity): self;

    /**
     * Generic count method to determine count of entities for specified criteria and search term(s).
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     *
     * @return integer
     *
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countAdvanced(?array $criteria = null, ?array $search = null): int;

    /** @noinspection GenericObjectTypeUsageInspection */
    /**
     * Wrapper for default Doctrine repository find method.
     *
     * @param string   $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     *
     * @return EntityInterface|mixed|null
     */
    public function find(string $id, ?int $lockMode = null, ?int $lockVersion = null);

    /** @noinspection GenericObjectTypeUsageInspection */
    /**
     * Wrapper for default Doctrine repository findOneBy method.
     *
     * @param mixed[]      $criteria
     * @param mixed[]|null $orderBy
     *
     * @return EntityInterface|mixed|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null);

    /**
     * Wrapper for default Doctrine repository findBy method.
     *
     * @param mixed[]       $criteria
     * @param string[]|null $orderBy
     * @param int|null      $limit
     * @param int|null      $offset
     *
     * @return array<EntityInterface>|EntityInterface[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Generic replacement for basic 'findBy' method if/when you want to use generic LIKE search.
     *
     * @param mixed[]      $criteria
     * @param mixed[]|null $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @param mixed[]|null $search
     *
     * @return array<EntityInterface>|EntityInterface[]
     */
    public function findByAdvanced(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $search = null
    ): array;

    /**
     * Wrapper for default Doctrine repository findBy method.
     *
     * @return array<EntityInterface>|EntityInterface[]
     */
    public function findAll(): array;

    /**
     * Repository method to fetch current entity id values from database and return those as an array.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     *
     * @return string[]
     */
    public function findIds(?array $criteria = null, ?array $search = null): array;

    /**
     * Helper method to 'reset' repository entity table - in other words delete all records - so be carefully with
     * this...
     *
     * @return integer
     */
    public function reset(): int;

    /**
     * With this method you can attach some custom functions for generic REST API find / count queries.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function processQueryBuilder(QueryBuilder $queryBuilder): void;

    /**
     * Adds left join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::leftJoin() for parameters
     *
     * @param mixed[] $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function addLeftJoin(array $parameters): self;

    /**
     * Adds inner join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::innerJoin() for parameters
     *
     * @param mixed[] $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function addInnerJoin(array $parameters): self;

    /**
     * Method to add callback to current query builder instance which is calling 'processQueryBuilder' method. By
     * default this method is called from following core methods:
     *  - countAdvanced
     *  - findByAdvanced
     *  - findIds
     *
     * Note that every callback will get 'QueryBuilder' as in first parameter.
     *
     * @param callable     $callable
     * @param mixed[]|null $args
     *
     * @return BaseRepositoryInterface
     */
    public function addCallback(callable $callable, ?array $args = null): self;
}
