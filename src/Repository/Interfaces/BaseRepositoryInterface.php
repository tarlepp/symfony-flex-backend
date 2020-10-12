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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Throwable;

/**
 * Interface BaseRepositoryInterface
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface BaseRepositoryInterface
{
    /**
     * Getter method for entity name.
     */
    public function getEntityName(): string;

    /**
     * Getter method for search columns of current entity.
     *
     * @return array<int, string>
     */
    public function getSearchColumns(): array;

    /**
     * Gets a reference to the entity identified by the given type and
     * identifier without actually loading it, if the entity is not yet loaded.
     *
     * @return object|null
     *
     * @throws ORMException
     */
    public function getReference(string $id);

    /**
     * Gets all association mappings of the class.
     *
     * @return array<string, string>
     */
    public function getAssociations(): array;

    /**
     * Returns the ORM metadata descriptor for a class.
     */
    public function getClassMetaData(): ClassMetadataInfo;

    /**
     * Getter method for EntityManager for current entity.
     */
    public function getEntityManager(): EntityManager;

    /**
     * Method to create new query builder for current entity.
     */
    public function createQueryBuilder(?string $alias = null, ?string $indexBy = null): QueryBuilder;

    /**
     * Wrapper for default Doctrine repository find method.
     */
    public function find(string $id, ?int $lockMode = null, ?int $lockVersion = null): ?EntityInterface;

    /**
     * Advanced version of find method, with this you can process query as you
     * like, eg. add joins and callbacks to modify / optimize current query.
     *
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
     * @param array<int|string, string|array> $criteria
     * @param array<int, string>|null $orderBy
     *
     * @return EntityInterface|object|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null);

    /**
     * Wrapper for default Doctrine repository findBy method.
     *
     * @param array<int|string, string|array> $criteria
     * @param array<int, string>|null $orderBy
     *
     * @return array<int, EntityInterface|object>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Generic replacement for basic 'findBy' method if/when you want to use
     * generic LIKE search.
     *
     * @param array<int|string, string|array> $criteria
     * @param array<string, string>|null $orderBy
     * @param array<string, string>|null $search
     *
     * @return array<int, EntityInterface>
     *
     * @throws Throwable
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
     * Repository method to fetch current entity id values from database and
     * return those as an array.
     *
     * @param array<int|string, string|array>|null $criteria
     * @param array<string, string>|null $search
     *
     * @return array<int, string>
     *
     * @throws InvalidArgumentException
     */
    public function findIds(?array $criteria = null, ?array $search = null): array;

    /**
     * Generic count method to determine count of entities for specified
     * criteria and search term(s).
     *
     * @param array<int|string, string|array>|null $criteria
     * @param array<string, string>|null $search
     *
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countAdvanced(?array $criteria = null, ?array $search = null): int;

    /**
     * Helper method to 'reset' repository entity table - in other words delete
     * all records - so be carefully with this...
     */
    public function reset(): int;

    /**
     * Helper method to persist specified entity to database.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(EntityInterface $entity, ?bool $flush = null): self;

    /**
     * Helper method to remove specified entity from database.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(EntityInterface $entity, ?bool $flush = null): self;

    /**
     * With this method you can attach some custom functions for generic REST
     * API find / count queries.
     */
    public function processQueryBuilder(QueryBuilder $queryBuilder): void;

    /**
     * Adds left join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::leftJoin() for parameters
     *
     * @param array<int, array<int, string>> $parameters
     *
     * @throws InvalidArgumentException
     */
    public function addLeftJoin(array $parameters): self;

    /**
     * Adds inner join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::innerJoin() for parameters
     *
     * @param array<int, array<int, string>> $parameters
     *
     * @throws InvalidArgumentException
     */
    public function addInnerJoin(array $parameters): self;

    /**
     * Method to add callback to current query builder instance which is
     * calling 'processQueryBuilder' method. By default this method is called
     * from following core methods:
     *  - countAdvanced
     *  - findByAdvanced
     *  - findIds
     *
     * Note that every callback will get 'QueryBuilder' as in first parameter.
     *
     * @param array<int, mixed>|null $args
     */
    public function addCallback(callable $callable, ?array $args = null): self;
}
