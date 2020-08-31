<?php
declare(strict_types = 1);
/**
 * /src/Repository/Traits/RepositoryMethodsTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository\Traits;

use App\Entity\Interfaces\EntityInterface;
use App\Rest\RepositoryHelper;
use App\Rest\UuidHelper;
use ArrayIterator;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use function array_map;
use function array_values;

/**
 * Trait RepositoryMethodsTrait
 *
 * @package App\Repository\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RepositoryMethodsTrait
{
    public function find(string $id, ?int $lockMode = null, ?int $lockVersion = null): ?EntityInterface
    {
        $output = $this->getEntityManager()->find($this->getEntityName(), $id, $lockMode, $lockVersion);

        return $output instanceof EntityInterface ? $output : null;
    }

    /**
     * {@inheritdoc}
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

        /*
         * This is just to help debug queries
         *
         * dd($queryBuilder->getQuery()->getDQL(), $queryBuilder->getQuery()->getSQL());
         */

        return $queryBuilder->getQuery()->getOneOrNullResult($hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $repository = $this->getEntityManager()->getRepository($this->getEntityName());

        return $repository instanceof EntityRepository ? $repository->findOneBy($criteria, $orderBy) : null;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return array<int, EntityInterface>
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

        /*
         * This is just to help debug queries
         *
         * dd($queryBuilder->getQuery()->getDQL(), $queryBuilder->getQuery()->getSQL());
         */
        RepositoryHelper::resetParameterCount();

        $iterator = (new Paginator($queryBuilder, true))->getIterator();

        return $iterator instanceof ArrayIterator ? $iterator->getArrayCopy() : iterator_to_array($iterator);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return array<int, string>
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

        /*
         * This is just to help debug queries
         *
         * dd($queryBuilder->getQuery()->getDQL(), $queryBuilder->getQuery()->getSQL());
         */
        RepositoryHelper::resetParameterCount();

        return array_values(array_map('\strval', array_map('\current', $queryBuilder->getQuery()->getArrayResult())));
    }

    public function countAdvanced(?array $criteria = null, ?array $search = null): int
    {
        // Get query builder
        $queryBuilder = $this->getQueryBuilder($criteria, $search);

        // Build query
        $queryBuilder->select('COUNT(DISTINCT(entity.id))');

        // Process custom QueryBuilder actions
        $this->processQueryBuilder($queryBuilder);

        /*
         * This is just to help debug queries
         *
         * dd($queryBuilder->getQuery()->getDQL(), $queryBuilder->getQuery()->getSQL());
         */
        RepositoryHelper::resetParameterCount();

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

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
     * @param array<int|string, string|array>|null $criteria
     * @param array<string, string>|null $search
     * @param array<string, string>|null $orderBy
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
