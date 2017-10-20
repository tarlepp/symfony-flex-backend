<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceSave.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\Entity\EntityInterface;

/**
 * Trait RestResourceSave
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceSave
{
    /**
     * Before lifecycle method for save method.
     *
     * @param EntityInterface $entity
     */
    public function beforeSave(EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for save method.
     *
     * @param EntityInterface $entity
     */
    public function afterSave(EntityInterface $entity): void
    {
    }
}
