<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceDelete.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\Entity\EntityInterface;

/**
 * Trait RestResourceDelete
 *
 * @SuppressWarnings("unused")
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceDelete
{
    /**
     * Before lifecycle method for delete method.
     *
     * @param string          $id
     * @param EntityInterface $entity
     */
    public function beforeDelete(string &$id, EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for delete method.
     *
     * Notes:   If you make changes to entity in this lifecycle method by default it will be saved on end of current
     *          request. To prevent this you need to detach current entity from entity manager.
     *
     * @param string          $id
     * @param EntityInterface $entity
     */
    public function afterDelete(string &$id, EntityInterface $entity): void
    {
    }
}
