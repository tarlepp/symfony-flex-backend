<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceFind.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits;

use App\Entity\Interfaces\EntityInterface;

/**
 * Trait RestResourceFind
 *
 * @SuppressWarnings("unused")
 *
 * @package App\Rest\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceFind
{
    /**
     * Before lifecycle method for find method.
     *
     * @param mixed[] $criteria
     * @param mixed[] $orderBy
     * @param mixed[] $search
     */
    public function beforeFind(array &$criteria, array &$orderBy, ?int &$limit, ?int &$offset, array &$search): void
    {
    }

    /**
     * After lifecycle method for find method.
     *
     * Notes: If you make changes to entities in this lifecycle method by default it will be saved on end of current
     *          request. To prevent this you need to clone each entity and use those.
     *
     * @param mixed[] $criteria
     * @param mixed[] $orderBy
     * @param mixed[] $search
     * @param EntityInterface[] $entities
     */
    public function afterFind(
        array &$criteria,
        array &$orderBy,
        ?int &$limit,
        ?int &$offset,
        array &$search,
        array &$entities
    ): void {
    }
}
