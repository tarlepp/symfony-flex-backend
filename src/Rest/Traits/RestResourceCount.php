<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/RestResourceCount.php
 */

namespace App\Rest\Traits;

/**
 * @SuppressWarnings("unused")
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
