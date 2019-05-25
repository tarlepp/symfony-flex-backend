<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/DtoTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\DTO;

use App\DTO\RestDtoInterface;
use App\DTO\User;
use App\Tests\Integration\Dto\src\DummyDto;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class GenericDtoTest
 *
 * @package App\Tests\Integration\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class GenericDtoTest extends KernelTestCase
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatPatchThrowsAnExceptionIfGetterMethodDoesNotExist(): void
    {
        /** @var MockObject|RestDtoInterface $dtoMock */
        $dtoMock = $this->createMock(RestDtoInterface::class);

        $dtoMock
            ->expects(static::once())
            ->method('getVisited')
            ->willReturn(['foo']);

        $dto = new User();
        $dto->patch($dtoMock);

        unset($dto, $dtoMock);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Property 'foo' has multiple getter methods - this is insane!
     */
    public function testThatPatchThrowsAnErrorIfMultipleGettersAreDefined(): void
    {
        require_once __DIR__ . '/src/DummyDto.php';

        $dtoMock = new DummyDto();
        $dtoMock->setFoo('foo');

        $dto = new User();
        $dto->patch($dtoMock);

        unset($dto, $dtoMock);
    }
}
