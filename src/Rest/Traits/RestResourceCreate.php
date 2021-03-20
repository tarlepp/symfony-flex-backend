<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceCreate.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;

/**
 * Trait RestResourceCreate
 *
 * @SuppressWarnings("unused")
 *
 * @package App\Rest\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait RestResourceCreate
{
    /**
     * Before lifecycle method for create method.
     */
    public function beforeCreate(RestDtoInterface $restDto, EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for create method.
     *
     * Notes: If you make changes to entity in this lifecycle method by default it will be saved on end of current
     *          request. To prevent this you need to detach current entity from entity manager.
     *
     *          Also note that if you've made some changes to entity and you eg. throw an exception within this method
     *          your entity will be saved if it has eg Blameable / Timestampable traits attached.
     */
    public function afterCreate(RestDtoInterface $restDto, EntityInterface $entity): void
    {
    }
}
