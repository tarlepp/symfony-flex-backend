<?php
declare(strict_types=1);
/**
 * /src/Repository/Base.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class Base
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class Base extends EntityRepository
{
    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = [];
}
