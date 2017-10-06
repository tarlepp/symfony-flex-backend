<?php
declare(strict_types=1);
/**
 * /src/Rest/Repository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use App\Entity\EntityInterface;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class Repository
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class Repository extends EntityRepository implements RepositoryInterface, LoggerAwareInterface
{
    const INNER_JOIN = 'innerJoin';
    const LEFT_JOIN = 'leftJoin';

    // Traits
    use LoggerAwareTrait;

    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = [];

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
     * Getter method for entity name.
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return parent::getEntityName();
    }

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
        return $this->getEntityManager()->getReference($this->getClassName(), $id);
    }

    /**
     * Gets all association mappings of the class.
     *
     * @return array
     */
    public function getAssociations(): array
    {
        return $this->getEntityManager()->getClassMetadata($this->getClassName())->getAssociationMappings();
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
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        self::$entityManager = parent::getEntityManager();

        if (!self::$entityManager->isOpen()) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            /** @noinspection StaticInvocationViaThisInspection */
            self::$entityManager = static::$entityManager->create(
                self::$entityManager->getConnection(),
                self::$entityManager->getConfiguration()
            );
        }

        return self::$entityManager;
    }

    /**
     * Helper method to persist specified entity to database.
     *
     * @param EntityInterface $entity
     *
     * @return RepositoryInterface
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function save(EntityInterface $entity): RepositoryInterface
    {
        // Persist on database
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $this;
    }

    /**
     * Helper method to remove specified entity from database.
     *
     * @param EntityInterface $entity
     *
     * @return RepositoryInterface
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function remove(EntityInterface $entity): RepositoryInterface
    {
        // Remove from database
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();

        return $this;
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
        $queryBuilder = $this->createQueryBuilder('entity');

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
        $queryBuilder = $this->createQueryBuilder('entity');

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

        // Create new query builder
        $queryBuilder = $this->createQueryBuilder('entity');

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
        $queryBuilder = $this->createQueryBuilder('entity');

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
     * @return RepositoryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function addLeftJoin(array $parameters): RepositoryInterface
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
     * @return RepositoryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function addInnerJoin(array $parameters): RepositoryInterface
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
     * @return RepositoryInterface
     */
    public function addCallback(callable $callable, array $args = null): RepositoryInterface
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
