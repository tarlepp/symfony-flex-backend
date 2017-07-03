<?php
declare(strict_types=1);
/**
 * /src/Repository/RoleRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\Role as Entity;
use App\Rest\Repository;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */

/**
 * Class RoleRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method Entity|null find($id, $lockMode = null, $lockVersion = null)
 */
class RoleRepository extends Repository
{
    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['id'];
}
