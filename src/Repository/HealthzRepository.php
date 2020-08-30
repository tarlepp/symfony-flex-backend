<?php
declare(strict_types = 1);
/**
 * /src/Repository/HealthzRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository;

use App\Entity\Healthz as Entity;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Throwable;

/**
 * Class HealthzRepository
 *
 * @package App\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method array<int, Entity> findAdvanced(string $id, $hydrationMode = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method array<int, Entity> findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @method array<int, Entity> findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null)
 * @method array<int, Entity> findAll()
 *
 * @codingStandardsIgnoreEnd
 */
class HealthzRepository extends BaseRepository
{
    protected static string $entityName = Entity::class;

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

        return $query->getOneOrNullResult();
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
        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->sub(new DateInterval('P7D'));

        // Create query builder
        $queryBuilder = $this
            ->createQueryBuilder('h')
            ->delete()
            ->where('h.timestamp < :timestamp')
            ->setParameter('timestamp', $date);

        // Return deleted row count
        return (int)$queryBuilder->getQuery()->execute();
    }
}
