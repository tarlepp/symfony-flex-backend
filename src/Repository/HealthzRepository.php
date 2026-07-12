<?php
declare(strict_types = 1);

/**
 * /src/Repository/HealthzRepository.php
 */

namespace App\Repository;

use App\Entity\Healthz as Entity;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Throwable;

/**
 * @extends BaseRepository<Entity>
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null)
 * @method Entity|null findAdvanced(string $id, string | int | null $hydrationMode = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<Entity> findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @method list<Entity> findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null)
 * @method list<Entity> findAll()
 *
 * @codingStandardsIgnoreEnd
 */
class HealthzRepository extends BaseRepository
{
    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Method to read value from database
     *
     * @throws NonUniqueResultException
     */
    public function read(): ?Entity
    {
        $query = $this
            ->createQueryBuilder('h')
            ->select('h')
            ->orderBy('h.timestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        /** @var Entity|null $result */
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * Method to write new value to database.
     *
     * @throws Throwable
     */
    public function create(): Entity
    {
        // Create new entity
        $entity = new Entity();

        // Store entity to database
        $this->save($entity);

        return $entity;
    }

    /**
     * Method to cleanup 'healthz' table.
     *
     * @throws Exception
     */
    public function cleanup(): int
    {
        // Determine date
        $date = new DateTimeImmutable(timezone: new DateTimeZone('UTC'))
            ->sub(new DateInterval('P7D'));

        // Create query builder
        $queryBuilder = $this
            ->createQueryBuilder('h')
            ->delete()
            ->where('h.timestamp < :timestamp')
            ->setParameter('timestamp', $date, Types::DATETIME_IMMUTABLE);

        // Return deleted row count
        return (int)$queryBuilder->getQuery()
            ->execute();
    }
}
