<?php
declare(strict_types=1);
/**
 * /src/Repository/BaseRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\EntityInterface;
use App\Repository\Traits\BaseRepositoryTrait;
use App\Rest\RepositoryHelper;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Class BaseRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    // Traits
    use BaseRepositoryTrait;

    const INNER_JOIN = 'innerJoin';
    const LEFT_JOIN = 'leftJoin';

    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = [];

    /**
     * @var string
     */
    protected static $entityName;

    /**
     * @var EntityManager
     */
    protected static $entityManager;

    /**
     * Joins that need to attach to queries, this is needed for to prevent duplicate joins on those.
     *
     * @var array
     */
    private static $joins = [
        self::INNER_JOIN => [],
        self::LEFT_JOIN  => [],
    ];

    /**
     * @var array
     */
    private static $processedJoins = [
        self::INNER_JOIN => [],
        self::LEFT_JOIN  => [],
    ];

    /**
     * @var array
     */
    private static $callbacks = [];

    /**
     * @var array
     */
    private static $processedCallbacks = [];

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * BaseRepository constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Getter method for entity name.
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return static::$entityName;
    }

    /**
     * Getter method for search columns of current entity.
     *
     * @return string[]
     */
    public function getSearchColumns(): array
    {
        return static::$searchColumns;
    }

    /**
     * Getter method for EntityManager for current entity.
     *
     * @return EntityManager|ObjectManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->managerRegistry->getManagerForClass(static::$entityName);
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
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countAdvanced(array $criteria = null, array $search = null): int
    {
        $criteria = $criteria ?? [];
        $search = $search ?? [];

        // Create new query builder
        $queryBuilder = $this->createQueryBuilder();

        // Process normal and search term criteria
        RepositoryHelper::processCriteria($queryBuilder, $criteria);
        RepositoryHelper::processSearchTerms($queryBuilder, $search, $this->getSearchColumns());

        $queryBuilder->select('COUNT(entity.id)');
        $queryBuilder->distinct();

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        RepositoryHelper::resetParameterCount();

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Wrapper for default Doctrine repository find method.
     *
     * @param string      $id
     * @param string|null $lockMode
     * @param string|null $lockVersion
     *
     * @return EntityInterface|null
     */
    public function find(string $id, string $lockMode = null, string $lockVersion = null): ?EntityInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getEntityManager()->find($this->getEntityName(), $id, $lockMode, $lockVersion);
    }

    /**
     * Wrapper for default Doctrine repository findOneBy method.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return EntityInterface|null
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?EntityInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getEntityManager()->getRepository($this->getEntityName())->findOneBy($criteria, $orderBy);
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
        $orderBy = $orderBy ?? [];
        $limit = $limit ?? 0;
        $offset = $offset ?? 0;
        $search = $search ?? [];

        // Create new query builder
        $queryBuilder = $this->createQueryBuilder();

        // Process normal and search term criteria and order
        RepositoryHelper::processCriteria($queryBuilder, $criteria);
        RepositoryHelper::processSearchTerms($queryBuilder, $search, $this->getSearchColumns());
        RepositoryHelper::processOrderBy($queryBuilder, $orderBy);

        // Process limit and offset
        $limit === 0 ?: $queryBuilder->setMaxResults($limit);
        $offset === 0 ?: $queryBuilder->setFirstResult($offset);

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
        return $this->getEntityManager()->getRepository($this->getEntityName())->findAll();
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
        $criteria = $criteria ?? [];
        $search = $search ?? [];
        $queryBuilder = $this->createQueryBuilder();

        // Process normal and search term criteria
        RepositoryHelper::processCriteria($queryBuilder, $criteria);
        RepositoryHelper::processSearchTerms($queryBuilder, $search, $this->getSearchColumns());

        $queryBuilder
            ->select('entity.id')
            ->distinct();

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        RepositoryHelper::resetParameterCount();

        return \array_map('\current', $queryBuilder->getQuery()->getArrayResult());
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
        return $queryBuilder->getQuery()->execute();
    }

    /**
     * With this method you can attach some custom functions for generic REST API find / count queries.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return void
     */
    public function processQueryBuilder(QueryBuilder $queryBuilder): void
    {
        // Reset processed joins and callbacks
        self::$processedJoins = [self::INNER_JOIN => [], self::LEFT_JOIN  => []];
        self::$processedCallbacks = [];

        $this->processJoins($queryBuilder);
        $this->processCallbacks($queryBuilder);
    }

    /**
     * Adds left join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::leftJoin() for parameters
     *
     * @param array $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function addLeftJoin(array $parameters): BaseRepositoryInterface
    {
        $this->addJoinToQuery('leftJoin', $parameters);

        return $this;
    }

    /**
     * Adds inner join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::innerJoin() for parameters
     *
     * @param array $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function addInnerJoin(array $parameters): BaseRepositoryInterface
    {
        $this->addJoinToQuery('innerJoin', $parameters);

        return $this;
    }

    /**
     * Method to add callback to current query builder instance which is calling 'processQueryBuilder' method. By
     * default this method is called from following core methods:
     *  - countAdvanced
     *  - findByAdvanced
     *  - findIds
     *
     * Note that every callback will get 'QueryBuilder' as in first parameter.
     *
     * @param callable   $callable
     * @param array|null $args
     *
     * @return BaseRepositoryInterface
     */
    public function addCallback(callable $callable, array $args = null): BaseRepositoryInterface
    {
        $args = $args ?? [];
        $hash = \sha1(\serialize(\array_merge([\spl_object_hash((object)$callable)], $args)));

        if (!\in_array($hash, self::$processedCallbacks, true)) {
            self::$callbacks[$hash] = [$callable, $args];
            self::$processedCallbacks[] = $hash;
        }

        return $this;
    }

    /**
     * Process defined joins for current QueryBuilder instance.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function processJoins(QueryBuilder $queryBuilder): void
    {
        /**
         * @var string $joinType
         * @var array  $joins
         */
        foreach (self::$joins as $joinType => $joins) {
            foreach ($joins as $key => $joinParameters) {
                $queryBuilder->$joinType(...$joinParameters);
            }

            self::$joins[$joinType] = [];
        }
    }

    /**
     * Process defined callbacks for current QueryBuilder instance.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function processCallbacks(QueryBuilder $queryBuilder): void
    {
        /**
         * @var callable $callback
         * @var array    $args
         */
        foreach (self::$callbacks as [$callback, $args]) {
            \array_unshift($args, $queryBuilder);

            $callback(...$args);
        }

        self::$callbacks = [];
    }

    /**
     * Method to add defined join(s) to current QueryBuilder query. This will keep track of attached join(s) so any of
     * those are not added multiple times to QueryBuilder.
     *
     * @note processJoins() method must be called for joins to actually be added to QueryBuilder. processQueryBuilder()
     *       method calls this method automatically.
     *
     * @see QueryBuilder::leftJoin()
     * @see QueryBuilder::innerJoin()
     *
     * @param string $type       Join type; leftJoin, innerJoin or join
     * @param array  $parameters Query builder join parameters.
     *
     * @throws \InvalidArgumentException
     */
    protected function addJoinToQuery(string $type, array $parameters): void
    {
        if (!\array_key_exists($type, self::$joins)) {
            throw new \InvalidArgumentException('Join type \'' . $type . '\' is not supported.');
        }

        $comparision = \implode('|', $parameters);

        if (!\in_array($comparision, self::$processedJoins[$type], true)) {
            self::$joins[$type][] = $parameters;

            self::$processedJoins[$type][] = $comparision;
        }
    }
}
