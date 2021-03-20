<?php
declare(strict_types = 1);
/**
 * /src/Repository/LogLoginFailureRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Repository;

use App\Entity\LogLoginFailure as Entity;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class LogLoginFailureRepository
 *
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
class LogLoginFailureRepository extends BaseRepository
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
     * Method to clear specified user login failures.
     */
    public function clear(User $user): int
    {
        // Create query builder and define delete query
        $queryBuilder = $this
            ->createQueryBuilder('logLoginFailure')
            ->delete()
            ->where('logLoginFailure.user = :user')
            ->setParameter('user', $user, Types::OBJECT);

        // Return deleted row count
        return (int)$queryBuilder->getQuery()->execute();
    }
}
