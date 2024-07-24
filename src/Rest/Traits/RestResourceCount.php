<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceCount.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits;

/**
 * @SuppressWarnings("unused")
 *
 * @package App\Rest\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait RestResourceCount
{
    /**
     * Before lifecycle method for count method.
     *
     * @param mixed[] $criteria
     * @param mixed[] $search
     */
    public function beforeCount(array &$criteria, array &$search): void
    {
    }

    /**
     * Before lifecycle method for count method.
     *
     * @param mixed[] $criteria
     * @param mixed[] $search
     */
    public function afterCount(array &$criteria, array &$search, int &$count): void
    {
    }
}
