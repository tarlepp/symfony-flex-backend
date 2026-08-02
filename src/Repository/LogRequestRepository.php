<?php
declare(strict_types = 1);

/**
 * /src/Repository/LogRequestRepository.php
 */

namespace App\Repository;

use App\Entity\LogRequest as Entity;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

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
class LogRequestRepository extends BaseRepository
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
     * Helper method to clean history data from request_log table.
     *
     * @throws Exception
     */
    public function cleanHistory(): int
    {
        // Determine date
        $date = new DateTimeImmutable(timezone: new DateTimeZone('UTC'))
            ->sub(new DateInterval('P3Y'));

        // Create query builder and define delete query
        $queryBuilder = $this
            ->createQueryBuilder('requestLog')
            ->delete()
            ->where('requestLog.date < :date')
            ->setParameter('date', $date->format('Y-m-d'));

        // Return deleted row count
        return (int)$queryBuilder->getQuery()
            ->execute();
    }
}
