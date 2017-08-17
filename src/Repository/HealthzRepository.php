<?php
declare(strict_types=1);
/**
 * /src/Repository/HealthzRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\Healthz as Entity;
use App\Rest\Repository;
use Doctrine\ORM\AbstractQuery;

/** @noinspection PhpHierarchyChecksInspection */
/**
 * Class HealthzRepository
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
class HealthzRepository extends Repository
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
     * @return Entity
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(): Entity
    {
        // Create new entity
        $entity = new Entity();
        $entity->setTimestamp(new \DateTime('NOW', new \DateTimeZone('UTC')));

        // Store entity to database
        $this->save($entity);

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
        $date = new \DateTime('NOW', new \DateTimeZone('UTC'));
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
