<?php
declare(strict_types=1);
/**
 * /src/Repository/UserGroupRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\UserGroup as Entity;
use App\Rest\Repository;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */

/**
 * Class UserGroupRepository
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
class UserGroupRepository extends Repository
{
    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['role', 'name'];
}
