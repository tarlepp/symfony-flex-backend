<?php
declare(strict_types=1);
/**
 * /src/Repository/RoleRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

/**
 * Class RoleRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleRepository extends Base
{
    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['role'];
}
