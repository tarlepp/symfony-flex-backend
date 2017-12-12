<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceUpdate.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;

/**
 * Trait RestResourceUpdate
 *
 * @SuppressWarnings("unused")
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceUpdate
{
    /**
     * Before lifecycle method for update method.
     *
     * @param string           $id
     * @param RestDtoInterface $dto
     * @param EntityInterface  $entity
     */
    public function beforeUpdate(string &$id, RestDtoInterface $dto, EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for update method.
     *
     * @param string           $id
     * @param RestDtoInterface $dto
     * @param EntityInterface  $entity
     */
    public function afterUpdate(string &$id, RestDtoInterface $dto, EntityInterface $entity): void
    {
    }
}
