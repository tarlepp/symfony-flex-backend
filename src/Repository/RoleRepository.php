<?php
declare(strict_types = 1);
/**
 * /src/Repository/RoleRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository;

use App\Entity\Role as Entity;

/**
 * Class RoleRepository
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
class RoleRepository extends BaseRepository
{
    protected static string $entityName = Entity::class;
}
