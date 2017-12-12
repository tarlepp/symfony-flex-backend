<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceIds.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

/**
 * Trait RestResourceIds
 *
 * @SuppressWarnings("unused")
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceIds
{
    /**
     * Before lifecycle method for ids method.
     *
     * @param array $criteria
     * @param array $search
     */
    public function beforeIds(array &$criteria, array &$search): void
    {
    }

    /**
     * Before lifecycle method for ids method.
     *
     * @param array $criteria
     * @param array $search
     * @param array $ids
     */
    public function afterIds(array &$criteria, array &$search, array &$ids): void
    {
    }
}
