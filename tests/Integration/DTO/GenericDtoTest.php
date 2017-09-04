<?php
declare(strict_types=1);
/**
 * /tests/Integration/DTO/DtoTestCase.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\DTO;

use App\DTO\RestDto;
use App\DTO\RestDtoInterface;
use App\DTO\User;
use App\Entity\EntityInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class GenericDtoTest
 *
 * @package App\Tests\Integration\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class GenericDtoTest extends KernelTestCase
{
    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatPatchThrowsAnExceptionIfGetterMethodDoesNotExist(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|RestDtoInterface $dtoMock */
        $dtoMock = $this->createMock(RestDtoInterface::class);

        $dtoMock
            ->expects(static::once())
            ->method('getVisited')
            ->willReturn(['foo']);

        $dto = new User();
        $dto->patch($dtoMock);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Property 'foo' has multiple getter methods - this is insane!
     */
    public function testThatPatchThrowsAnErrorIfMultipleGettersAreDefined(): void
    {
        $dtoMock = new Dto();
        $dtoMock->setFoo('foo');

        $dto = new User();
        $dto->patch($dtoMock);
    }
}

/**
 * Class Dto
 *
 * @package App\Tests\Integration\DTO
 */
class Dto extends RestDto
{
    /**
     * @var mixed
     */
    private $foo;

    /**
     * @param mixed $foo
     *
     * @return Dto
     */
    public function setFoo($foo): Dto
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
     * Method to load DTO data from specified entity.
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
     * Method to update specified entity with DTO data.
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
