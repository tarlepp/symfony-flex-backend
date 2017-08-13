<?php
declare(strict_types=1);
/**
 * /src/Repository/RequestLogRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Rest\Repository;

/**
 * Class RequestLogRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
