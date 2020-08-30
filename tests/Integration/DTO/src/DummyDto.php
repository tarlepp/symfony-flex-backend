<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/src/DummyDto.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO\src;

use App\Dto\RestDto;
use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DummyDto
 *
 * @package App\Tests\Integration\Dto\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
    public function setFoo($foo): self
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

    public function isFoo(): bool
    {
        return (bool)$this->foo;
    }

    public function hasFoo(): bool
    {
        return (bool)$this->foo;
    }

    /**
     * Method to create DTO from request object.
     */
    public function createFromRequest(Request $request): RestDtoInterface
    {
        return new self();
    }

    /**
     * Method to load DummyDto data from specified entity.
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        return $this;
    }

    /**
     * Method to update specified entity with DummyDto data.
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        parent::update($entity);

        return $entity;
    }
}
