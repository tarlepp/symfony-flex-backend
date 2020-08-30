<?php
declare(strict_types = 1);
/**
 * /src/Repository/LogLoginFailureRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository;

use App\Entity\LogLoginFailure as Entity;
use App\Entity\User;

/**
 * Class LogLoginFailureRepository
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
class LogLoginFailureRepository extends BaseRepository
{
    protected static string $entityName = Entity::class;

    /**
     * Method to clear specified user login failures.
     */
    public function clear(User $user): int
    {
        // Create query builder and define delete query
        $queryBuilder = $this
            ->createQueryBuilder('logLoginFailure')
            ->delete()
            ->where('logLoginFailure.user = :user')
            ->setParameter('user', $user);

        // Return deleted row count
        return (int)$queryBuilder->getQuery()->execute();
    }
}
