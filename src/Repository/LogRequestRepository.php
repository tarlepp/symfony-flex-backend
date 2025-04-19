<?php
declare(strict_types = 1);
/**
 * /src/Repository/LogRequestRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Repository;

use App\Entity\LogRequest as Entity;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @package App\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method Entity|null findAdvanced(string $id, string | int | null $hydrationMode = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Entity[] findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @method Entity[] findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null)
 * @method Entity[] findAll()
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
        return (int)$queryBuilder->getQuery()->execute();
    }
}
