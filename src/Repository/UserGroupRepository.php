<?php
declare(strict_types = 1);

/**
 * /src/Repository/UserGroupRepository.php
 */

namespace App\Repository;

use App\Entity\UserGroup as Entity;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

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
class UserGroupRepository extends BaseRepository
{
    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    /**
     * @var array<int, string>
     */
    protected static array $searchColumns = ['role', 'name'];

    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
    }
}
