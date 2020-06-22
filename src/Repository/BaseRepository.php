<?php
declare(strict_types = 1);
/**
 * /src/Repository/BaseRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\Traits\RepositoryMethodsTrait;
use App\Repository\Traits\RepositoryWrappersTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use function array_merge;
use function array_unshift;
use function count;
use function implode;
use function in_array;
use function serialize;
use function sha1;
use function spl_object_hash;

/**
 * Class BaseRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    use RepositoryMethodsTrait;
    use RepositoryWrappersTrait;

    private const INNER_JOIN = 'innerJoin';
    private const LEFT_JOIN = 'leftJoin';

    protected static array $searchColumns = [];
    protected static string $entityName;
    protected static EntityManager $entityManager;

    /**
     * Joins that need to attach to queries, this is needed for to prevent duplicate joins on those.
     *
     * @var array<string, array<int, mixed>>
     */
    private static array $joins = [
        self::INNER_JOIN => [],
        self::LEFT_JOIN => [],
    ];

    /**
     * @var array<string, array<int, mixed>>
     */
    private static array $processedJoins = [
        self::INNER_JOIN => [],
        self::LEFT_JOIN => [],
    ];

    /**
     * @var array<int, array<int, mixed|callable>>
     */
    private static array $callbacks = [];

    /**
     * @var array<int, string>
     */
    private static array $processedCallbacks = [];

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
     * @return array<int, string>
     */
    public function getSearchColumns(): array
    {
        return static::$searchColumns;
    }

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
    public function save(EntityInterface $entity, ?bool $flush = null): BaseRepositoryInterface
    {
        $flush ??= true;

        // Persist on database
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

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
    public function remove(EntityInterface $entity, ?bool $flush = null): BaseRepositoryInterface
    {
        $flush ??= true;

        // Remove from database
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    /**
     * With this method you can attach some custom functions for generic REST API find / count queries.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function processQueryBuilder(QueryBuilder $queryBuilder): void
    {
        // Reset processed joins and callbacks
        self::$processedJoins = [self::INNER_JOIN => [], self::LEFT_JOIN => []];
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
     * @param array<int, mixed> $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws InvalidArgumentException
     */
    public function addLeftJoin(array $parameters): BaseRepositoryInterface
    {
        if (count($parameters) > 1) {
            $this->addJoinToQuery(self::LEFT_JOIN, $parameters);
        }

        return $this;
    }

    /**
     * Adds inner join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::innerJoin() for parameters
     *
     * @param array<int, mixed> $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws InvalidArgumentException
     */
    public function addInnerJoin(array $parameters): BaseRepositoryInterface
    {
        if (count($parameters) > 0) {
            $this->addJoinToQuery(self::INNER_JOIN, $parameters);
        }

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
     * @param callable               $callable
     * @param array<int, mixed>|null $args
     *
     * @return BaseRepositoryInterface
     */
    public function addCallback(callable $callable, ?array $args = null): BaseRepositoryInterface
    {
        $args ??= [];
        $hash = sha1(serialize(array_merge([spl_object_hash((object)$callable)], $args)));

        if (!in_array($hash, self::$processedCallbacks, true)) {
            self::$callbacks[] = [$callable, $args];
            self::$processedCallbacks[] = $hash;
        }

        return $this;
    }

    /**
     * Process defined joins for current QueryBuilder instance.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function processJoins(QueryBuilder $queryBuilder): void
    {
        foreach (self::$joins as $joinType => $joins) {
            foreach ($joins as $joinParameters) {
                $queryBuilder->{$joinType}(...$joinParameters);
            }

            self::$joins[$joinType] = [];
        }
    }

    /**
     * Process defined callbacks for current QueryBuilder instance.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function processCallbacks(QueryBuilder $queryBuilder): void
    {
        foreach (self::$callbacks as [$callback, $args]) {
            array_unshift($args, $queryBuilder);

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
     * @param string  $type       Join type; leftJoin, innerJoin or join
     * @param mixed[] $parameters Query builder join parameters
     */
    private function addJoinToQuery(string $type, array $parameters): void
    {
        $comparision = implode('|', $parameters);

        if (!in_array($comparision, self::$processedJoins[$type], true)) {
            self::$joins[$type][] = $parameters;

            self::$processedJoins[$type][] = $comparision;
        }
    }
}
