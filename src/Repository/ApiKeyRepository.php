<?php
declare(strict_types = 1);
/**
 * /src/Repository/ApiKeyRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Repository;

use App\DTO\User\User;
use App\Entity\ApiKey as Entity;

/**
 * Class ApiKeyRepository
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
class ApiKeyRepository extends BaseRepository
{
    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;
}
