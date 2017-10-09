<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/RestResourceCreate.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;

/**
 * Trait RestResourceCreate
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceCreate
{
    /**
     * Before lifecycle method for create method.
     *
     * @param RestDtoInterface $dto
     * @param EntityInterface  $entity
     */
    public function beforeCreate(RestDtoInterface $dto, EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for create method.
     *
     * @param RestDtoInterface $dto
     * @param EntityInterface  $entity
     */
    public function afterCreate(RestDtoInterface $dto, EntityInterface $entity): void
    {
    }
}
