<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/src/DummyDto.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Dto\src;

use App\Dto\RestDto;
use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;

/**
 * Class DummyDto
 *
 * @package App\Tests\Integration\Dto\src
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DummyDto extends RestDto
{
    /**
     * @var mixed
     */
    private $foo;

    /**
     * @param mixed $foo
     *
     * @return DummyDto
     */
    public function setFoo($foo): DummyDto
    {
        $this->setVisited('foo');

        $this->foo = $foo;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFoo()
    {
        return $this->foo;
    }

    /**
     * @return bool
     */
    public function isFoo(): bool
    {
        return (bool)$this->foo;
    }

    /**
     * @return bool
     */
    public function hasFoo(): bool
    {
        return (bool)$this->foo;
    }

    /**
     * Method to load DummyDto data from specified entity.
     *
     * @param EntityInterface $entity
     *
     * @return RestDtoInterface
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        return $this;
    }

    /**
     * Method to update specified entity with DummyDto data.
     *
     * @param EntityInterface $entity
     *
     * @return EntityInterface
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        return $entity;
    }
}
