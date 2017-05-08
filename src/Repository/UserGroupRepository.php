<?php
declare(strict_types=1);
/**
 * /src/Repository/UserGroupRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

/**
 * Class UserGroupRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupRepository extends Base
{
    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['role', 'name'];
}
