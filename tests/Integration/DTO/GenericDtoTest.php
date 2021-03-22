<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/GenericDtoTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class GenericDtoTest
 *
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class GenericDtoTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `patch` method throws an exception if `getter` method does not exist on DTO class
     */
    public function testThatPatchThrowsAnExceptionIfGetterMethodDoesNotExist(): void
    {
        $this->expectException(BadMethodCallException::class);

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
     * @testdox Test that `determineGetterMethod` method returns `$expected` when using `$dto::$property` property
     */
    public function testThatDetermineGetterMethodReturnsExpected(
        string $expected,
        string $property,
        RestDtoInterface $dto,
    ): void {
        static::assertSame($expected, PhpUnitUtil::callMethod($dto, 'determineGetterMethod', [$dto, $property]));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `patch` method throws an exception if DTO class contains multiple `getters` for same property
     */
    public function testThatPatchThrowsAnErrorIfMultipleGettersAreDefined(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Property \'foo\' has multiple getter methods - this is insane!');

        $dtoMock = (new DummyDto())
            ->setFoo('foo');

        (new User())
            ->patch($dtoMock);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `patch` method calls expected methods
     */
    public function testThatPatchCallsExpectedMethods(): void
    {
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

        /**
         * @var User $dto
         */
        $dto = (new User())
            ->patch($dtoUser);

        static::assertSame('username', $dto->getUsername());
        static::assertSame('email@com', $dto->getEmail());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `update` method calls expected methods
     */
    public function testThatUpdateMethodCallsExpectedMethods(): void
    {
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

    /**
     * @return Generator<array{0: string, 1: string, 2: User}>
     */
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
