<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceFindOneBy.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits;

use App\Entity\Interfaces\EntityInterface;

/**
 * Trait RestResourceFindOneBy
 *
 * @SuppressWarnings("unused")
 *
 * @package App\Rest\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceFindOneBy
{
    /**
     * Before lifecycle method for findOneBy method.
     *
     * @param mixed[] $criteria
     * @param mixed[] $orderBy
     */
    public function beforeFindOneBy(array &$criteria, array &$orderBy): void
    {
    }

    /**
     * After lifecycle method for findOneBy method.
     *
     * Notes: If you make changes to entity in this lifecycle method by default it will be saved on end of current
     *          request. To prevent this you need to detach current entity from entity manager.
     *
     *          Also note that if you've made some changes to entity and you eg. throw an exception within this method
     *          your entity will be saved if it has eg Blameable / Timestampable traits attached.
     *
     * @param mixed[] $criteria
     * @param mixed[] $orderBy
     */
    public function afterFindOneBy(array &$criteria, array &$orderBy, ?EntityInterface $entity): void
    {
    }
}
