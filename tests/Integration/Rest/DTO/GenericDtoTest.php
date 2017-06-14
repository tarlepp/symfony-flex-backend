<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/DTO/DtoTestCase.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\DTO;

use App\Rest\DTO\RestDtoInterface;
use App\Rest\DTO\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class GenericDtoTest
 *
 * @package App\Tests\Integration\Rest\DTO
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
}
