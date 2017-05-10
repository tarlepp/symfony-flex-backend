<?php
declare(strict_types=1);
/**
 * /src/Repository/UserRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Rest\Repository;

/**
 * Class UserRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserRepository extends Repository
{
    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['username', 'firstname', 'surname', 'email'];
}
