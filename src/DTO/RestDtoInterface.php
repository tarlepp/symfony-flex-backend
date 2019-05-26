<?php
declare(strict_types = 1);
/**
 * /src/DTO/RestDtoInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO;

use App\Entity\EntityInterface;
use BadMethodCallException;
use LogicException;

/**
 * Interface RestDtoInterface
 *
 * @package App\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface RestDtoInterface
{
    /**
     * Getter method for visited setters. This is needed for dto patching.
     *
     * @return string[]
     */
    public function getVisited(): array;

    /**
     * Setter for visited data. This is needed for dto patching.
     *
     * @param string $property
     *
     * @return RestDtoInterface
     */
    public function setVisited(string $property): self;

    /**
     * Method to patch current dto with another one.
     *
     * @param RestDtoInterface $dto
     *
     * @return RestDtoInterface
     *
     * @throws LogicException
     * @throws BadMethodCallException
     */
    public function patch(RestDtoInterface $dto): self;

    /**
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface $entity
     *
     * @return RestDtoInterface
     */
    public function load(EntityInterface $entity): self;

    /**
     * Method to update specified entity with DTO data.
     *
     * @param EntityInterface $entity
     *
     * @return EntityInterface
     */
    public function update(EntityInterface $entity): EntityInterface;
}
