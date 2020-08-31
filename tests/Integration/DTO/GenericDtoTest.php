<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/DtoTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\DTO;

use App\DTO\RestDtoInterface;
use App\DTO\User\User;
use App\Entity\User as UserEntity;
use App\Tests\Integration\DTO\src\DummyDto;
use App\Utils\Tests\PhpUnitUtil;
use BadMethodCallException;
use Generator;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class GenericDtoTest
 *
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class GenericDtoTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatPatchThrowsAnExceptionIfGetterMethodDoesNotExist(): void
    {
        $this->expectException(BadMethodCallException::class);

        /** @var MockObject|RestDtoInterface $dtoMock */
        $dtoMock = $this->createMock(RestDtoInterface::class);

        $dtoMock
            ->expects(static::once())
            ->method('getVisited')
            ->willReturn(['foo']);

        (new User())
            ->patch($dtoMock);
    }

    /**
     * @dataProvider dataProviderTestThatDetermineGetterMethodReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `determineGetterMethod` method returns `$expected` when using `$dto::$$property` property.
     */
    public function testThatDetermineGetterMethodReturnsExpected(
        string $expected,
        string $property,
        RestDtoInterface $dto
    ): void {
        static::assertSame($expected, PhpUnitUtil::callMethod($dto, 'determineGetterMethod', [$dto, $property]));
    }

    public function testThatPatchThrowsAnErrorIfMultipleGettersAreDefined(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Property \'foo\' has multiple getter methods - this is insane!');

        require_once __DIR__ . '/src/DummyDto.php';

        $dtoMock = (new DummyDto())
            ->setFoo('foo');

        (new User())
            ->patch($dtoMock);
    }

    /**
     * @throws Throwable
     */
    public function testThatPatchCallsExpectedMethods(): void
    {
        /** @var MockObject|User $dtoUser */
        $dtoUser = $this->createMock(User::class);

        $dtoUser
            ->expects(static::once())
            ->method('getVisited')
            ->willReturn(['username', 'email']);

        $dtoUser
            ->expects(static::once())
            ->method('getUsername')
            ->willReturn('username');

        $dtoUser
            ->expects(static::once())
            ->method('getEmail')
            ->willReturn('email@com');

        $dto = (new User())
            ->patch($dtoUser);

        static::assertSame('username', $dto->getUsername());
        static::assertSame('email@com', $dto->getEmail());
    }

    /**
     * @throws Throwable
     */
    public function testThatUpdateMethodCallsExpectedMethods(): void
    {
        /** @var MockObject|UserEntity $userEntity */
        $userEntity = $this->createMock(UserEntity::class);

        $userEntity
            ->expects(static::once())
            ->method('setUsername')
            ->with('username')
            ->willReturn($userEntity);

        $userEntity
            ->expects(static::once())
            ->method('setEmail')
            ->with('email@com')
            ->willReturn($userEntity);

        $userEntity
            ->expects(static::once())
            ->method('setPlainPassword')
            ->with('password')
            ->willReturn($userEntity);

        (new User())
            ->setUsername('username')
            ->setEmail('email@com')
            ->setPassword('password')
            ->update($userEntity);
    }

    public function dataProviderTestThatDetermineGetterMethodReturnsExpected(): Generator
    {
        yield [
            'getUsername',
            'username',
            new User(),
        ];

        yield [
            'getEmail',
            'email',
            new User(),
        ];

        // TODO: implement test cases for `has` and `is` methods
    }
}
