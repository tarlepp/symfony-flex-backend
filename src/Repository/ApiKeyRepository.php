<?php
declare(strict_types = 1);
/**
 * /src/Repository/ApiKeyRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository;

use App\Entity\ApiKey as Entity;

/**
 * Class ApiKeyRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null                           find(string $id, ?int $lockMode = null, ?int $lockVersion = null): ?Entity
 * @method array<array-key|Entity, mixed>|Entity findAdvanced(string $id, $hydrationMode = null)
 * @method Entity|null                           findOneBy(array $criteria, ?array $orderBy = null): ?Entity
 * @method Entity[]                              findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
 * @method Entity[]                              findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null): array
 * @method Entity[]                              findAll(): array
 *
 * @codingStandardsIgnoreEnd
 */
class ApiKeyRepository extends BaseRepository
{
    /**
     * @var string
     */
    protected static $entityName = Entity::class;
}
