<?php
declare(strict_types = 1);
/**
 * /src/Repository/Traits/RepositoryMethodsTrait.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository\Traits;

use App\Entity\EntityInterface;
use App\Repository\BaseRepositoryInterface;
use App\Rest\RepositoryHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

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
 * @method void          processQueryBuilder(QueryBuilder $queryBuilder): void;
 */
trait RepositoryMethodsTrait
{
    /** @noinspection GenericObjectTypeUsageInspection */
    /**
     * Wrapper for default Doctrine repository find method.
     *
     * @param string   $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     *
     * @return EntityInterface|object|null
     *
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function find(string $id, int $lockMode = null, int $lockVersion = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getEntityManager()
            ->find($this->getEntityName(), $id, $lockMode, $lockVersion);
    }

    /** @noinspection GenericObjectTypeUsageInspection */
    /**
     * Wrapper for default Doctrine repository findOneBy method.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return EntityInterface|object|null
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getEntityManager()
            ->getRepository($this->getEntityName())
            ->/** @scrutinizer ignore-call */findOneBy($criteria, $orderBy);
    }

    /**
     * Wrapper for default Doctrine repository findBy method.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): array
    {
        return $this
            ->getEntityManager()
            ->getRepository($this->getEntityName())
            ->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Generic replacement for basic 'findBy' method if/when you want to use generic LIKE search.
     *
     * @param array        $criteria
     * @param null|array   $orderBy
     * @param null|integer $limit
     * @param null|integer $offset
     * @param null|array   $search
     *
     * @return EntityInterface[]
     *
     * @throws \InvalidArgumentException
     */
    public function findByAdvanced(
        array $criteria,
        array $orderBy = null,
        int $limit = null,
        int $offset = null,
        array $search = null
    ): array {
        // Get query builder
        $queryBuilder = $this->getQueryBuilder($criteria, $search, $orderBy, $limit, $offset);

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        RepositoryHelper::resetParameterCount();

        return (new Paginator($queryBuilder, true))->getIterator()->getArrayCopy();
    }

    /**
     * Wrapper for default Doctrine repository findBy method.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->getEntityManager()
            ->getRepository($this->getEntityName())
            ->findAll();
    }

    /**
     * Repository method to fetch current entity id values from database and return those as an array.
     *
     * @param null|array $criteria
     * @param null|array $search
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function findIds(array $criteria = null, array $search = null): array
    {
        // Get query builder
        $queryBuilder = $this->getQueryBuilder($criteria, $search);

        // Build query
        $queryBuilder
            ->select('entity.id')
            ->distinct();

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        RepositoryHelper::resetParameterCount();

        return \array_map('\current', $queryBuilder->getQuery()->getArrayResult());
    }

    /**
     * Generic count method to determine count of entities for specified criteria and search term(s).
     *
     * @param null|array $criteria
     * @param null|array $search
     *
     * @return integer
     *
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countAdvanced(array $criteria = null, array $search = null): int
    {
        // Get query builder
        $queryBuilder = $this->getQueryBuilder($criteria, $search);

        // Build query
        $queryBuilder
            ->select('COUNT(entity.id)')
            ->distinct();

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        RepositoryHelper::resetParameterCount();

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Helper method to 'reset' repository entity table - in other words delete all records - so be carefully with
     * this...
     *
     * @return integer
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
     * Helper method to persist specified entity to database.
     *
     * @param EntityInterface $entity
     *
     * @return BaseRepositoryInterface|$this
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function save(EntityInterface $entity)
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
     * @return BaseRepositoryInterface|$this
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function remove(EntityInterface $entity)
    {
        // Remove from database
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;
    }

    /**
     * Helper method to get QueryBuilder for current instance within specified default parameters.
     *
     * @param null|array $criteria
     * @param null|array $search
     * @param null|array $orderBy
     * @param null|int   $limit
     * @param null|int   $offset
     *
     * @return QueryBuilder
     *
     * @throws \InvalidArgumentException
     */
    private function getQueryBuilder(
        array $criteria = null,
        array $search = null,
        array $orderBy = null,
        int $limit = null,
        int $offset = null
    ): QueryBuilder {
        // Normalize inputs
        $criteria = $criteria ?? [];
        $search = $search ?? [];
        $orderBy = $orderBy ?? [];
        $limit = $limit ?? 0;
        $offset = $offset ?? 0;

        // Create new QueryBuilder for this instance
        $queryBuilder = $this->createQueryBuilder();

        // Process normal and search term criteria
        RepositoryHelper::processCriteria($queryBuilder, $criteria);
        RepositoryHelper::processSearchTerms($queryBuilder, $search, $this->getSearchColumns());

        // Process order if defined
        $orderBy === [] ?: RepositoryHelper::processOrderBy($queryBuilder, $orderBy);

        // Process limit and offset
        $limit === 0 ?: $queryBuilder->setMaxResults($limit);
        $offset === 0 ?: $queryBuilder->setFirstResult($offset);

        return $queryBuilder;
    }
}
