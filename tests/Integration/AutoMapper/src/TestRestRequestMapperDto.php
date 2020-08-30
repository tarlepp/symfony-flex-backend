<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/TestRestRequestMapperDto.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\AutoMapper\src;

use App\DTO\RestDto;
use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;

/**
 * Class TestRestRequestMapperDto
 *
 * @package App\Tests\Integration\AutoMapper\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class TestRestRequestMapperDto extends RestDto
{
    protected string $someProperty = '';
    protected string $someTransformProperty = '';

    /**
     * Method to load DTO data from specified entity.
     *
     * @return RestDtoInterface|TestRestRequestMapperDto
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        return $this;
    }

    public function getSomeProperty(): string
    {
        return $this->someProperty;
    }

    /**
     * @return TestRestRequestMapperDto
     */
    public function setSomeProperty(string $someProperty): self
    {
        $this->someProperty = $someProperty;

        return $this;
    }

    public function getSomeTransformProperty(): string
    {
        return $this->someTransformProperty;
    }

    /**
     * @return TestRestRequestMapperDto
     */
    public function setSomeTransformProperty(string $someTransformProperty): self
    {
        $this->someTransformProperty = $someTransformProperty;

        return $this;
    }
}
