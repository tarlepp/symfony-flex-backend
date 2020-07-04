<?php
declare(strict_types = 1);
/**
 * /src/Repository/LogLoginRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository;

use App\Entity\LogLogin as Entity;

/**
 * Class LogLoginRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null    find(string $id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method Entity[]|array findAdvanced(string $id, $hydrationMode = null)
 * @method Entity|null    findOneBy(array $criteria, ?array $orderBy = null)
 * @method Entity[]       findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @method Entity[]       findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null)
 * @method Entity[]       findAll()
 *
 * @codingStandardsIgnoreEnd
 */
class LogLoginRepository extends BaseRepository
{
    protected static string $entityName = Entity::class;
}
