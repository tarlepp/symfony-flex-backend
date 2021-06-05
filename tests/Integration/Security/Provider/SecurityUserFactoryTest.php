<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Provider/SecurityUserFactoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security\Provider;

use App\Entity\User;
use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
use App\Security\Provider\SecurityUserFactory;
use App\Security\RolesService;
use App\Security\SecurityUser;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;
use function assert;

/**
 * Class SecurityUserFactoryTest
 *
 * @package App\Tests\Integration\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class SecurityUserFactoryTest extends KernelTestCase
{
    private MockObject | UserRepository | null $userRepository = null;
    private MockObject | RolesService | null $rolesService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` method throws an exception when user is not found
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionIfUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User not found for UUID:');

        $this->getUserRepositoryMock()
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn(null);

        (new SecurityUserFactory($this->getUserRepository(), $this->getRolesService(), ''))
            ->loadUserByUsername('test_user');
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` method returns expected `SecurityUser` instance
     */
    public function testThatLoadByUsernameReturnsExpectedSecurityUser(): void
    {
        $user = new User();

        $this->getUserRepositoryMock()
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn($user);

        $this->getRolesServiceMock()
            ->expects(static::once())
            ->method('getInheritedRoles')
            ->with($user->getRoles())
            ->willReturn(['FOO', 'BAR']);

        $securityUser = (new SecurityUserFactory($this->getUserRepository(), $this->getRolesService(), ''))
            ->loadUserByUsername('test_user');

        static::assertSame($user->getId(), $securityUser->getUserIdentifier());
        static::assertSame(['FOO', 'BAR'], $securityUser->getRoles());
    }

    /**
     * @dataProvider dataProviderTestThatSupportsMethodsReturnsFalseWithNotSupportedType
     *
     * @testdox Test that `supportsClass` method returns `false` when using `$input` as input
     */
    public function testThatSupportsMethodsReturnsFalseWithNotSupportedType(bool | int | string $input): void
    {
        static::assertFalse(
            (new SecurityUserFactory($this->getUserRepository(), $this->getRolesService(), ''))
                ->supportsClass((string)$input)
        );
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `supportsClass` method returns `true` when using `SecurityUser::class` as input
     */
    public function testThatSupportsMethodsReturnsTrueWithSupportedType(): void
    {
        static::assertTrue(
            (new SecurityUserFactory($this->getUserRepository(), $this->getRolesService(), ''))
                ->supportsClass(SecurityUser::class)
        );
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `refreshUser` method throws an exception with not supported user instance
     */
    public function testThatRefreshUserThrowsAnExceptionWithNotSupportedUser(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectErrorMessageMatches('#^Invalid user class(.*)#');

        (new SecurityUserFactory($this->getUserRepository(), $this->getRolesService(), ''))
            ->refreshUser(new InMemoryUser('username', 'password'));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `refreshUser` method throws an exception when user is not found
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User not found for UUID:');

        $this->getUserRepositoryMock()
            ->expects(static::once())
            ->method('find')
            ->willReturn(null);

        (new SecurityUserFactory($this->getUserRepository(), $this->getRolesService(), ''))
            ->refreshUser(new SecurityUser(new User()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `refreshUser` method returns new instance of `SecurityUser` and it matches with old one
     */
    public function testThatRefreshUserReturnsNewInstanceOfSecurityUser(): void
    {
        $user = new User();
        $securityUser = new SecurityUser($user, ['FOO', 'BAR']);

        $this->getUserRepositoryMock()
            ->expects(static::once())
            ->method('find')
            ->with($securityUser->getUserIdentifier())
            ->willReturn($user);

        $this->getRolesServiceMock()
            ->expects(static::once())
            ->method('getInheritedRoles')
            ->with($user->getRoles())
            ->willReturn(['FOO', 'BAR']);

        $newSecurityUser = (new SecurityUserFactory($this->getUserRepository(), $this->getRolesService(), ''))
            ->refreshUser($securityUser);

        static::assertNotSame($securityUser, $newSecurityUser);
        static::assertSame($securityUser->getUserIdentifier(), $newSecurityUser->getUserIdentifier());
        static::assertSame($securityUser->getRoles(), $newSecurityUser->getRoles());
    }

    /**
     * @return Generator<array{0: boolean|string|int}>
     */
    public function dataProviderTestThatSupportsMethodsReturnsFalseWithNotSupportedType(): Generator
    {
        yield [true];
        yield ['foobar'];
        yield [123];
        yield [stdClass::class];
        yield [UserInterface::class];
        yield [UserEntity::class];
        yield [ApiKeyUser::class];
    }

    private function getUserRepository(): UserRepository
    {
        assert($this->userRepository instanceof UserRepository);

        return $this->userRepository;
    }

    private function getUserRepositoryMock(): MockObject
    {
        assert($this->userRepository instanceof MockObject);

        return $this->userRepository;
    }

    private function getRolesService(): RolesService
    {
        assert($this->rolesService instanceof RolesService);

        return $this->rolesService;
    }

    private function getRolesServiceMock(): MockObject
    {
        assert($this->rolesService instanceof MockObject);

        return $this->rolesService;
    }
}
