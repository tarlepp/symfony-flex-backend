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
use App\Tests\Utils\PhpUnitUtil;
use BadMethodCallException;
use Generator;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Integration\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class GenericDtoTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `patch` method throws an exception if `getter` method does not exist on DTO class')]
    public function testThatPatchThrowsAnExceptionIfGetterMethodDoesNotExist(): void
    {
        $this->expectException(BadMethodCallException::class);

        $dtoMock = $this->createMock(RestDtoInterface::class);

        $dtoMock
            ->expects($this->once())
            ->method('getVisited')
            ->willReturn(['foo']);

        new User()
            ->patch($dtoMock);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatDetermineGetterMethodReturnsExpected')]
    #[TestDox('Test that `determineGetterMethod` method returns `$expected` when using `$dto::$property` property')]
    public function testThatDetermineGetterMethodReturnsExpected(
        string $expected,
        string $property,
        RestDtoInterface $dto,
    ): void {
        self::assertSame($expected, PhpUnitUtil::callMethod($dto, 'determineGetterMethod', [$dto, $property]));
    }

    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that `patch` method throws an exception if DTO class contains multiple `getters` for same property'
    )]
    public function testThatPatchThrowsAnErrorIfMultipleGettersAreDefined(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Property \'foo\' has multiple getter methods - this is insane!');

        $dtoMock = new DummyDto()
            ->setFoo('foo');

        new User()
            ->patch($dtoMock);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `patch` method calls expected methods')]
    public function testThatPatchCallsExpectedMethods(): void
    {
        $dtoUser = $this->createMock(User::class);

        $dtoUser
            ->expects($this->once())
            ->method('getVisited')
            ->willReturn(['username', 'email']);

        $dtoUser
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('username');

        $dtoUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('email@com');

        $dto = new User()
            ->patch($dtoUser);

        self::assertInstanceOf(User::class, $dto);
        self::assertSame('username', $dto->getUsername());
        self::assertSame('email@com', $dto->getEmail());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `update` method calls expected methods')]
    public function testThatUpdateMethodCallsExpectedMethods(): void
    {
        $userEntity = $this->createMock(UserEntity::class);

        $userEntity
            ->expects($this->once())
            ->method('setUsername')
            ->with('username')
            ->willReturn($userEntity);

        $userEntity
            ->expects($this->once())
            ->method('setEmail')
            ->with('email@com')
            ->willReturn($userEntity);

        $userEntity
            ->expects($this->once())
            ->method('setPlainPassword')
            ->with('password')
            ->willReturn($userEntity);

        new User()
            ->setUsername('username')
            ->setEmail('email@com')
            ->setPassword('password')
            ->update($userEntity);
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: User}>
     */
    public static function dataProviderTestThatDetermineGetterMethodReturnsExpected(): Generator
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
