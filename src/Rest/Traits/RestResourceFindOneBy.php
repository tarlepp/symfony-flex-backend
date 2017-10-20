<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceFindOneBy.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\Entity\EntityInterface;

/**
 * Trait RestResourceFindOneBy
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceFindOneBy
{
    /**
     * Before lifecycle method for findOneBy method.
     *
     * @param array $criteria
     * @param array $orderBy
     */
    public function beforeFindOneBy(array &$criteria, array &$orderBy): void
    {
    }

    /**
     * After lifecycle method for findOneBy method.
     *
     * @param array                $criteria
     * @param array                $orderBy
     * @param null|EntityInterface $entity
     */
    public function afterFindOneBy(array &$criteria, array &$orderBy, EntityInterface $entity = null): void
    {
    }
}
