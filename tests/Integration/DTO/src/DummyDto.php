<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/src/DummyDto.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\src;

use App\DTO\RestDto;
use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use Override;

/**
 * @package App\Tests\Integration\Dto\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class DummyDto extends RestDto
{
    private string $foo = '';

    public function setFoo(string $foo): self
    {
        $this->setVisited('foo');

        $this->foo = $foo;

        return $this;
    }

    public function getFoo(): string
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
     * Method to load DummyDto data from specified entity.
     */
    #[Override]
    public function load(EntityInterface $entity): RestDtoInterface
    {
        return $this;
    }

    /**
     * Method to update specified entity with DummyDto data.
     */
    #[Override]
    public function update(EntityInterface $entity): EntityInterface
    {
        parent::update($entity);

        return $entity;
    }
}
