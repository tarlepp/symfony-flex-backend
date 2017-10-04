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
use Doctrine\ORM\Query\Expr\Composite;
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
     * Parameter count in current query, this is used to track parameters which are bind to current query.
     *
     * @var integer
     */
    private $parameterCount = 0;

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
        $this->processCriteria($queryBuilder, $criteria);
        $this->processSearchTerms($queryBuilder, $search);

        $queryBuilder->select('COUNT(entity.id)');
        $queryBuilder->distinct();

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

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
        $this->processCriteria($queryBuilder, $criteria);
        $this->processSearchTerms($queryBuilder, $search);
        $this->processOrderBy($queryBuilder, $orderBy);

        // Process limit and offset
        $limit === 0 ?: $queryBuilder->setMaxResults($limit);
        $offset === 0 ?: $queryBuilder->setFirstResult($offset);

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

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
        $this->processCriteria($queryBuilder, $criteria);
        $this->processSearchTerms($queryBuilder, $search);

        $queryBuilder
            ->select('entity.id')
            ->distinct();

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

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

    /**
     * Process given criteria which is given by ?where parameter. This is given as JSON string, which is converted
     * to assoc array for this process.
     *
     * Note that this supports by default (without any extra work) just 'eq' and 'in' expressions. See example array
     * below:
     *
     *  [
     *      'u.id'  => 3,
     *      'u.uid' => 'uid',
     *      'u.foo' => [1, 2, 3],
     *      'u.bar' => ['foo', 'bar'],
     *  ]
     *
     * And these you can make easily happen within REST controller and simple 'where' parameter. See example below:
     *
     *  ?where={"u.id":3,"u.uid":"uid","u.foo":[1,2,3],"u.bar":["foo","bar"]}
     *
     * Also note that you can make more complex use case fairly easy, just follow instructions below.
     *
     * If you're trying to make controller specified special criteria with projects generic Rest controller, just
     * add 'processCriteria(array &$criteria)' method to your own controller and pre-process that criteria in there
     * the way you want it to be handled. In other words just modify that basic key-value array just as you like it,
     * main goal is to create array that is compatible with 'getExpression' method in this class. For greater detail
     * just see that method comments.
     *
     * tl;dr Modify your $criteria parameter in your controller with 'processCriteria(array &$criteria)' method.
     *
     * @see \App\Rest\Repository::getExpression
     * @see \App\Controller\Rest::processCriteria
     *
     * @param QueryBuilder $queryBuilder
     * @param array        $criteria
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function processCriteria(QueryBuilder $queryBuilder, array $criteria): void
    {
        if (empty($criteria)) {
            return;
        }

        // Initialize condition array
        $condition = [];

        /**
         * Lambda function to create condition array for 'getExpression' method.
         *
         * @param string      $column
         * @param mixed       $value
         * @param null|string $operator
         *
         * @return array
         */
        $createCriteria = function (string $column, $value, $operator = null) {
            if (\strpos($column, '.') === false) {
                $column = 'entity.' . $column;
            }

            // Determine used operator
            if ($operator === null) {
                $operator = \is_array($value) ? 'in' : 'eq';
            }

            return [$column, $operator, $value];
        };

        /**
         * Lambda function to process criteria and add it to main condition array.
         *
         * @param mixed  $value
         * @param string $column
         */
        $processCriteria = function ($value, $column) use (&$condition, $createCriteria) {
            // If criteria contains 'and' OR 'or' key(s) assume that array in only in the right format
            if (\strcmp($column, 'and') === 0 || \strcmp($column, 'or') === 0) {
                $condition[$column] = $value;
            } else {
                // Add condition
                $condition[] = $createCriteria($column, $value);
            }
        };

        // Create used condition array
        \array_walk($criteria, $processCriteria);

        // And attach search term condition to main query
        $queryBuilder->andWhere($this->getExpression($queryBuilder, $queryBuilder->expr()->andX(), $condition));
    }

    /**
     * Helper method to process given search terms and create criteria about those. Note that each repository
     * has 'searchColumns' property which contains the fields where search term will be affected.
     *
     * @see \App\Controller\Rest::getSearchTerms
     *
     * @param QueryBuilder $queryBuilder
     * @param array $searchTerms
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function processSearchTerms(QueryBuilder $queryBuilder, array $searchTerms): void
    {
        $columns = $this->getSearchColumns();

        if (empty($columns)) {
            return;
        }

        // Iterate search term sets
        foreach ($searchTerms as $operand => $terms) {
            $criteria = SearchTerm::getCriteria($columns, $terms, $operand);

            if ($criteria !== null) {
                $queryBuilder->andWhere($this->getExpression($queryBuilder, $queryBuilder->expr()->andX(), $criteria));
            }
        }
    }

    /**
     * Simple process method for order by part of for current query builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param array        $orderBy
     *
     * @return void
     */
    protected function processOrderBy(QueryBuilder $queryBuilder, array $orderBy): void
    {
        foreach ($orderBy as $column => $order) {
            if (\strpos($column, '.') === false) {
                $column = 'entity.' . $column;
            }

            $queryBuilder->addOrderBy($column, $order);
        }
    }

    /**
     * Recursively takes the specified criteria and adds too the expression.
     *
     * The criteria is defined in an array notation where each item in the list
     * represents a comparison <fieldName, operator, value>. The operator maps to
     * comparison methods located in ExpressionBuilder. The key in the array can
     * be used to identify grouping of comparisons.
     *
     * Currently supported  Doctrine\ORM\Query\Expr methods:
     *
     * OPERATOR    EXAMPLE INPUT ARRAY             GENERATED QUERY RESULT      NOTES
     *  eq          ['u.id',  'eq',        123]     u.id = ?1                   -
     *  neq         ['u.id',  'neq',       123]     u.id <> ?1                  -
     *  lt          ['u.id',  'lt',        123]     u.id < ?1                   -
     *  lte         ['u.id',  'lte',       123]     u.id <= ?1                  -
     *  gt          ['u.id',  'gt',        123]     u.id > ?1                   -
     *  gte         ['u.id',  'gte',       123]     u.id >= ?1                  -
     *  in          ['u.id',  'in',        [1,2]]   u.id IN (1,2)               third value may contain n values
     *  notIn       ['u.id',  'notIn',     [1,2]]   u.id NOT IN (1,2)           third value may contain n values
     *  isNull      ['u.id',  'isNull',    null]    u.id IS NULL                third value must be set, but not used
     *  isNotNull   ['u.id',  'isNotNull', null]    u.id IS NOT NULL            third value must be set, but not used
     *  like        ['u.id',  'like',      'abc']   u.id LIKE ?1                -
     *  notLike     ['u.id',  'notLike',   'abc']   u.id NOT LIKE ?1            -
     *  between     ['u.id',  'between',  [1,6]]    u.id BETWEEN ?1 AND ?2      third value must contain two values
     *
     * Also note that you can easily combine 'and' and 'or' queries like following examples:
     *
     * EXAMPLE INPUT ARRAY                                  GENERATED QUERY RESULT
     *  [
     *      'and' => [
     *          ['u.firstname', 'eq',   'foo bar']
     *          ['u.surname',   'neq',  'not this one']
     *      ]
     *  ]                                                   (u.firstname = ?1 AND u.surname <> ?2)
     *  [
     *      'or' => [
     *          ['u.firstname', 'eq',   'foo bar']
     *          ['u.surname',   'neq',  'not this one']
     *      ]
     *  ]                                                   (u.firstname = ?1 OR u.surname <> ?2)
     *
     * Also note that you can nest these criteria arrays as many levels as you need - only the sky is the limit...
     *
     * @example
     *  $criteria = [
     *      'or' => [
     *          ['entity.field1', 'like', '%field1Value%'],
     *          ['entity.field2', 'like', '%field2Value%'],
     *      ],
     *      'and' => [
     *          ['entity.field3', 'eq', 3],
     *          ['entity.field4', 'eq', 'four'],
     *      ],
     *      ['entity.field5', 'neq', 5],
     *  ];
     *
     * $qb = $this->createQueryBuilder('entity');
     * $qb->where($this->getExpression($qb, $qb->expr()->andX(), $criteria));
     * $query = $qb->getQuery();
     * echo $query->getSQL();
     *
     * // Result:
     * // SELECT *
     * // FROM tableName
     * // WHERE ((field1 LIKE '%field1Value%') OR (field2 LIKE '%field2Value%'))
     * // AND ((field3 = '3') AND (field4 = 'four'))
     * // AND (field5 <> '5')
     *
     * Also note that you can nest these queries as many times as you wish...
     *
     * @see https://gist.github.com/jgornick/8671644
     *
     * @param QueryBuilder $queryBuilder
     * @param Composite    $expression
     * @param array        $criteria
     *
     * @return Composite
     *
     * @throws \InvalidArgumentException
     */
    protected function getExpression(
        QueryBuilder $queryBuilder,
        Composite $expression,
        array $criteria
    ): Composite {
        if (\count($criteria)) {
            foreach ($criteria as $key => $comparison) {
                if ($key === 'and' || \array_key_exists('and', $comparison)) {
                    $expression->add($this->getExpression($queryBuilder, $queryBuilder->expr()->andX(), $comparison));
                } elseif ($key === 'or' || \array_key_exists('or', $comparison)) {
                    $expression->add($this->getExpression($queryBuilder, $queryBuilder->expr()->orX(), $comparison));
                } else {
                    $comparison = (object)\array_combine(['field', 'operator', 'value'], $comparison);

                    // Increase parameter count
                    $this->parameterCount++;

                    // Initialize used callback parameters
                    $parameters = [$comparison->field];

                    $lowercaseOperator = \strtolower($comparison->operator);

                    // Array values needs some extra work
                    if (\is_array($comparison->value)) {
                        // Operator is between, so we need to add third parameter for Expr method
                        if ($lowercaseOperator === 'between') {
                            $parameters[] = '?' . $this->parameterCount;
                            $queryBuilder->setParameter($this->parameterCount, $comparison->value[0]);

                            $this->parameterCount++;

                            $parameters[] = '?' . $this->parameterCount;
                            $queryBuilder->setParameter($this->parameterCount, $comparison->value[1]);
                        } else { // Otherwise this must be IN or NOT IN expression
                            $parameters[] = \array_map(function ($value) use ($queryBuilder) {
                                return $queryBuilder->expr()->literal($value);
                            }, $comparison->value);
                        }
                    } elseif (!($lowercaseOperator === 'isnull' || $lowercaseOperator === 'isnotnull')) {
                        $parameters[] = '?' . $this->parameterCount;

                        $queryBuilder->setParameter($this->parameterCount, $comparison->value);
                    }

                    // And finally add new expression to main one with specified parameters
                    $expression->add(
                        \call_user_func_array([$queryBuilder->expr(), $comparison->operator], $parameters)
                    );
                }
            }
        }

        return $expression;
    }
}
