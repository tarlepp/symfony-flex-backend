<?php
declare(strict_types = 1);
/**
 * /src/Repository/Interfaces/BaseRepositoryInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository\Interfaces;

use App\Entity\Interfaces\EntityInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

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

    /**
     * Gets a reference to the entity identified by the given type and identifier without actually loading it,
     * if the entity is not yet loaded.
     *
     * @param string $id
     *
     * @return object|null
     *
     * @throws ORMException
     */
    public function getReference(string $id);

    /**
     * Gets all association mappings of the class.
     *
     * @return array|array<int, string>
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
     * Wrapper for default Doctrine repository find method.
     *
     * @param string   $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     *
     * @return EntityInterface|null
     */
    public function find(string $id, ?int $lockMode = null, ?int $lockVersion = null): ?EntityInterface;

    /**
     * Advanced version of find method, with this you can process query as you like, eg. add joins and callbacks to
     * modify / optimize current query.
     *
     * @param string     $id
     * @param string|int $hydrationMode
     *
     * @return array<int|string, mixed>|EntityInterface
     *
     * @throws NonUniqueResultException
     */
    public function findAdvanced(string $id, $hydrationMode = null);

    /**
     * Wrapper for default Doctrine repository findOneBy method.
     *
     * @param mixed[]      $criteria
     * @param mixed[]|null $orderBy
     *
     * @return EntityInterface|object|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null);

    /**
     * Wrapper for default Doctrine repository findBy method.
     *
     * @param mixed[]      $criteria
     * @param mixed[]|null $orderBy
     * @param int|null     $limit
     * @param int|null     $offset
     *
     * @return array<int, EntityInterface|object>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Generic replacement for basic 'findBy' method if/when you want to use generic LIKE search.
     *
     * @param mixed[]      $criteria
     * @param mixed[]|null $orderBy
     * @param int|null     $limit
     * @param int|null     $offset
     * @param mixed[]|null $search
     *
     * @return array<int, EntityInterface>
     *
     * @throws InvalidArgumentException
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
     * @return array<int, EntityInterface|object>
     */
    public function findAll(): array;

    /**
     * Repository method to fetch current entity id values from database and return those as an array.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     *
     * @return array<int, string>
     *
     * @throws InvalidArgumentException
     */
    public function findIds(?array $criteria = null, ?array $search = null): array;

    /**
     * Generic count method to determine count of entities for specified criteria and search term(s).
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     *
     * @return int
     *
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function countAdvanced(?array $criteria = null, ?array $search = null): int;

    /**
     * Helper method to 'reset' repository entity table - in other words delete all records - so be carefully with
     * this...
     *
     * @return int
     */
    public function reset(): int;

    /**
     * Helper method to persist specified entity to database.
     *
     * @param EntityInterface $entity
     * @param bool|null       $flush
     *
     * @return BaseRepositoryInterface
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(EntityInterface $entity, ?bool $flush = null): self;

    /**
     * Helper method to remove specified entity from database.
     *
     * @param EntityInterface $entity
     * @param bool|null       $flush
     *
     * @return BaseRepositoryInterface
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(EntityInterface $entity, ?bool $flush = null): self;

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
     * @param array<int, mixed> $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws InvalidArgumentException
     *
     * @see QueryBuilder::leftJoin() for parameters
     */
    public function addLeftJoin(array $parameters): self;

    /**
     * Adds inner join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @param array<int, mixed> $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws InvalidArgumentException
     *
     * @see QueryBuilder::innerJoin() for parameters
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
     * @param callable               $callable
     * @param array<int, mixed>|null $args
     *
     * @return BaseRepositoryInterface
     */
    public function addCallback(callable $callable, ?array $args = null): self;
}
