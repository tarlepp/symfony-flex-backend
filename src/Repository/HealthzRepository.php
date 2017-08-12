<?php
declare(strict_types=1);
/**
 * /src/Repository/HealthzRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\Healthz;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

/**
 * Class HealthzRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HealthzRepository extends EntityRepository
{
    /**
     * Method to read value from database
     *
     * @return string|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function read(): ?string
    {
        $query = $this
            ->createQueryBuilder('h')
            ->select('h.timestamp')
            ->orderBy('h.timestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $query->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * Method to write new value to database.
     *
     * @return Healthz
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(): Healthz
    {
        // Create new entity
        $entity = new Healthz();
        $entity->setTimestamp(new \DateTime('NOW', new \DateTimeZone('UTC')));

        // Get entity manager
        $em = $this->getEntityManager();

        // Store entity to database
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    /**
     * Method to cleanup 'healthz' table.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function cleanup(): int
    {
        // Determine date
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->sub(new \DateInterval('P7D'));

        // Create query builder
        $queryBuilder = $this
            ->createQueryBuilder('h')
            ->delete()
            ->where('h.timestamp < :timestamp')
            ->setParameter('timestamp', $date);

        // Return deleted row count
        return $queryBuilder->getQuery()->execute();
    }
}
