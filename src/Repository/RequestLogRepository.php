<?php
declare(strict_types=1);
/**
 * /src/Repository/RequestLogRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\RequestLog as Entity;
use App\Rest\Repository;

/** @noinspection PhpHierarchyChecksInspection */
/**
 * Class RequestLogRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method Entity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entity[]    findAll()
 * @method Entity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Entity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entity[]    findByAdvanced(array $criteria, array $orderBy = null, int $limit = null, int $offset = null, array $search = null): array
 */
class RequestLogRepository extends Repository
{
    /**
     * Helper method to clean history data from request_log table.
     *
     * @return integer
     *
     * @throws \Exception
     */
    public function cleanHistory(): int
    {
        // Determine date
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->sub(new \DateInterval('P3Y'));

        // Create query builder
        $queryBuilder = $this->createQueryBuilder('requestLog');

        // Define delete query
        $queryBuilder
            ->delete()
            ->where('requestLog.time < :time')
            ->setParameter('time', $date);

        // Return deleted row count
        return $queryBuilder->getQuery()->execute();
    }
}
