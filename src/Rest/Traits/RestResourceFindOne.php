<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceFindOne.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\Entity\EntityInterface;

/**
 * Trait RestResourceFindOne
 *
 * @SuppressWarnings("unused")
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceFindOne
{
    /**
     * Before lifecycle method for findOne method.
     *
     * @param string $id
     */
    public function beforeFindOne(string &$id): void
    {
    }

    /**
     * After lifecycle method for findOne method.
     *
     * @param string               $id
     * @param null|EntityInterface $entity
     */
    public function afterFindOne(string &$id, EntityInterface $entity = null): void
    {
    }
}
