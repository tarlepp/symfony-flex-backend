<?php
declare(strict_types=1);
/**
 * /src/Repository/LoginFailureLogRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\LoginFailureLog as Entity;
use App\Entity\User;
use App\Rest\Repository;

/** @noinspection PhpHierarchyChecksInspection */
/**
 * Class LoginFailureLogRepository
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
class LoginFailureLogRepository extends Repository
{
    /**
     * @return array
     */
    public function getFailedAttempts(): array
    {
        // Create query
        $qb = $this->createQueryBuilder('log')
            ->select('log, COUNT(log.id) AS _count')
            ->groupBy('log.user')
            ->orderBy('log.timestamp', 'DESC');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param User $user
     *
     * @return int
     */
    public function clearFailuresForUser(User $user): int
    {
        // Create DELETE query
        $qb = $this->createQueryBuilder('log')
            ->delete()
            ->where('log.user = :user')
            ->setParameter('user', $user->getId());

        return (int)$qb->getQuery()->execute();
    }
}
