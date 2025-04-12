<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/TestRestRequestMapperDto.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper\src;

use App\DTO\RestDto;
use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use Override;

/**
 * @package App\Tests\Integration\AutoMapper\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class TestRestRequestMapperDto extends RestDto
{
    protected string $someProperty = '';
    protected string $someTransformProperty = '';

    #[Override]
    public function load(EntityInterface $entity): RestDtoInterface
    {
        return $this;
    }

    public function getSomeProperty(): string
    {
        return $this->someProperty;
    }

    public function setSomeProperty(string $someProperty): self
    {
        $this->someProperty = $someProperty;

        return $this;
    }

    public function getSomeTransformProperty(): string
    {
        return $this->someTransformProperty;
    }

    public function setSomeTransformProperty(string $someTransformProperty): self
    {
        $this->someTransformProperty = $someTransformProperty;

        return $this;
    }
}
