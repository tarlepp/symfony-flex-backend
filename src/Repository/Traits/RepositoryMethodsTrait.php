<?php
declare(strict_types = 1);
/**
 * /src/Repository/Traits/RepositoryMethodsTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository\Traits;

use App\Entity\EntityInterface;
use App\Rest\RepositoryHelper;
use App\Rest\UuidHelper;
use ArrayIterator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\TransactionRequiredException;
use InvalidArgumentException;
use Throwable;
use function array_map;
use function array_values;

/**
 * Trait RepositoryMethodsTrait
 *
 * @package App\Repository\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method EntityManager getEntityManager(): EntityManager
 * @method string        getEntityName(): string
 * @method array         getSearchColumns(): array
 * @method QueryBuilder  createQueryBuilder(string $alias = null, string $indexBy = null): QueryBuilder
 * @method void          processQueryBuilder(QueryBuilder $queryBuilder): void
 */
trait RepositoryMethodsTrait
{
    /**
     * Wrapper for default Doctrine repository find method.
     *
     * @param string   $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     *
     * @return EntityInterface|null
     *
     * @throws TransactionRequiredException
     * @throws OptimisticLockException
     * @throws ORMInvalidArgumentException
     * @throws ORMException
     */
    public function find(string $id, ?int $lockMode = null, ?int $lockVersion = null): ?EntityInterface
    {
        $output = $this->getEntityManager()->find($this->getEntityName(), $id, $lockMode, $lockVersion);

        return $output instanceof EntityInterface ? $output : null;
    }

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
    public function findAdvanced(string $id, $hydrationMode = null)
    {
        // Get query builder
        $queryBuilder = $this->getQueryBuilder();

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        $queryBuilder
            ->where('entity.id = :id')
            ->setParameter('id', $id, UuidHelper::getType($id));

        /**
         * This is just to help debug queries
         *
         * dd($queryBuilder->getQuery()->getDQL(), $queryBuilder->getQuery()->getSQL());
         */

        return $queryBuilder->getQuery()->getOneOrNullResult($hydrationMode);
    }

    /**
     * Wrapper for default Doctrine repository findOneBy method.
     *
     * @param mixed[]      $criteria
     * @param mixed[]|null $orderBy
     *
     * @return EntityInterface|object|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $repository = $this->getEntityManager()->getRepository($this->getEntityName());

        return $repository instanceof EntityRepository ? $repository->findOneBy($criteria, $orderBy) : null;
    }

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
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return array_values(
            $this
                ->getEntityManager()
                ->getRepository($this->getEntityName())
                ->findBy($criteria, $orderBy, $limit, $offset)
        );
    }

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
     * @throws Throwable
     */
    public function findByAdvanced(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $search = null
    ): array {
        // Get query builder
        $queryBuilder = $this->getQueryBuilder($criteria, $search, $orderBy, $limit, $offset);

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        /**
         * This is just to help debug queries
         *
         * dd($queryBuilder->getQuery()->getDQL(), $queryBuilder->getQuery()->getSQL());
         */

        RepositoryHelper::resetParameterCount();

        $iterator = (new Paginator($queryBuilder, true))->getIterator();

        return $iterator instanceof ArrayIterator ? $iterator->getArrayCopy() : iterator_to_array($iterator);
    }

    /**
     * Wrapper for default Doctrine repository findBy method.
     *
     * @return array<int, EntityInterface|object>
     */
    public function findAll(): array
    {
        return array_values(
            $this->getEntityManager()
                ->getRepository($this->getEntityName())
                ->findAll()
        );
    }

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
    public function findIds(?array $criteria = null, ?array $search = null): array
    {
        // Get query builder
        $queryBuilder = $this->getQueryBuilder($criteria, $search);

        // Build query
        $queryBuilder
            ->select('entity.id')
            ->distinct();

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        /**
         * This is just to help debug queries
         *
         * dd($queryBuilder->getQuery()->getDQL(), $queryBuilder->getQuery()->getSQL());
         */

        RepositoryHelper::resetParameterCount();

        return array_values(array_map('\strval', array_map('\current', $queryBuilder->getQuery()->getArrayResult())));
    }

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
     * @throws NoResultException
     */
    public function countAdvanced(?array $criteria = null, ?array $search = null): int
    {
        // Get query builder
        $queryBuilder = $this->getQueryBuilder($criteria, $search);

        // Build query
        $queryBuilder->select('COUNT(DISTINCT(entity.id))');

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        /**
         * This is just to help debug queries
         *
         * dd($queryBuilder->getQuery()->getDQL(), $queryBuilder->getQuery()->getSQL());
         */

        RepositoryHelper::resetParameterCount();

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Helper method to 'reset' repository entity table - in other words delete all records - so be carefully with
     * this...
     *
     * @return int
     */
    public function reset(): int
    {
        // Create query builder
        $queryBuilder = $this->createQueryBuilder();

        // Define delete query
        $queryBuilder->delete();

        // Return deleted row count
        return (int)$queryBuilder->getQuery()->execute();
    }

    /**
     * Helper method to get QueryBuilder for current instance within specified default parameters.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     * @param mixed[]|null $orderBy
     * @param int|null     $limit
     * @param int|null     $offset
     *
     * @return QueryBuilder
     *
     * @throws InvalidArgumentException
     */
    private function getQueryBuilder(
        ?array $criteria = null,
        ?array $search = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): QueryBuilder {
        // Create new QueryBuilder for this instance
        $queryBuilder = $this->createQueryBuilder();

        // Process normal and search term criteria
        RepositoryHelper::processCriteria($queryBuilder, $criteria);
        RepositoryHelper::processSearchTerms($queryBuilder, $this->getSearchColumns(), $search);
        RepositoryHelper::processOrderBy($queryBuilder, $orderBy);

        // Process limit and offset
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset ?? 0);

        return $queryBuilder;
    }
}
