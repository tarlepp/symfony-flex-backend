<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceFind.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\Entity\EntityInterface;

/**
 * Trait RestResourceFind
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceFind
{
    /**
     * Before lifecycle method for find method.
     *
     * @param array   $criteria
     * @param array   $orderBy
     * @param integer $limit
     * @param integer $offset
     * @param array   $search
     */
    public function beforeFind(array &$criteria, array &$orderBy, int &$limit, int &$offset, array &$search): void
    {
    }

    /**
     * After lifecycle method for find method.
     *
     * @param array             $criteria
     * @param array             $orderBy
     * @param integer           $limit
     * @param integer           $offset
     * @param array             $search
     * @param EntityInterface[] $entities
     */
    public function afterFind(
        array &$criteria,
        array &$orderBy,
        int &$limit,
        int &$offset,
        array &$search,
        array &$entities
    ): void {
    }
}
