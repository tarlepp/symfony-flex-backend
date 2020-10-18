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
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User as CoreUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

/**
 * Class SecurityUserFactoryTest
 *
 * @package App\Tests\Integration\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class SecurityUserFactoryTest extends KernelTestCase
{
    /**
     * @var MockObject|UserRepository
     */
    private $userRepository;

    /**
     * @var MockObject|RolesService
     */
    private $rolesService;

    /**
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` method throws an exception when user is not found
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionIfUserNotFound(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User not found for UUID:');

        $this->userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn(null);

        (new SecurityUserFactory($this->userRepository, $this->rolesService, ''))
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

        $this->userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn($user);

        $this->rolesService
            ->expects(static::once())
            ->method('getInheritedRoles')
            ->with($user->getRoles())
            ->willReturn(['FOO', 'BAR']);

        $securityUser = (new SecurityUserFactory($this->userRepository, $this->rolesService, ''))
            ->loadUserByUsername('test_user');

        static::assertSame($user->getId(), $securityUser->getUsername());
        static::assertSame(['FOO', 'BAR'], $securityUser->getRoles());
    }

    /**
     * @dataProvider dataProviderTestThatSupportsMethodsReturnsFalseWithNotSupportedType
     *
     * @param mixed $input
     *
     * @testdox Test that `supportsClass` method returns `false` when using `$input` as input
     */
    public function testThatSupportsMethodsReturnsFalseWithNotSupportedType($input): void
    {
        static::assertFalse(
            (new SecurityUserFactory($this->userRepository, $this->rolesService, ''))
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
            (new SecurityUserFactory($this->userRepository, $this->rolesService, ''))
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
        $this->expectExceptionMessage('Invalid user class "Symfony\Component\Security\Core\User\User"');

        (new SecurityUserFactory($this->userRepository, $this->rolesService, ''))
            ->refreshUser(new CoreUser('test_user', 'password'));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `refreshUser` method throws an exception when user is not found
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserNotFound(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User not found for UUID:');

        $this->userRepository
            ->expects(static::once())
            ->method('find')
            ->willReturn(null);

        (new SecurityUserFactory($this->userRepository, $this->rolesService, ''))
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

        $this->userRepository
            ->expects(static::once())
            ->method('find')
            ->with($securityUser->getUsername())
            ->willReturn($user);

        $this->rolesService
            ->expects(static::once())
            ->method('getInheritedRoles')
            ->with($user->getRoles())
            ->willReturn(['FOO', 'BAR']);

        $newSecurityUser = (new SecurityUserFactory($this->userRepository, $this->rolesService, ''))
            ->refreshUser($securityUser);

        static::assertNotSame($securityUser, $newSecurityUser);
        static::assertSame($securityUser->getUsername(), $newSecurityUser->getUsername());
        static::assertSame($securityUser->getRoles(), $newSecurityUser->getRoles());
    }

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
}
