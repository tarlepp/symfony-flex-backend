<?php
declare(strict_types = 1);
/**
 * /src/Repository/UserGroupRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository;

use App\Entity\UserGroup as Entity;

/**
 * Class UserGroupRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null                     find(string $id, ?int $lockMode = null, ?int $lockVersion = null): ?Entity
 * @method array<int|string, mixed>|Entity findAdvanced(string $id, $hydrationMode = null)
 * @method Entity|null                     findOneBy(array $criteria, ?array $orderBy = null): ?Entity
 * @method array<int, Entity>              findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
 * @method array<int, Entity>              findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null): array
 * @method array<int, Entity>              findAll(): array
 *
 * @codingStandardsIgnoreEnd
 */
class UserGroupRepository extends BaseRepository
{
    /**
     * @var string
     */
    protected static $entityName = Entity::class;

    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['role', 'name'];
}
