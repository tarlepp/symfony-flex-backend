<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Provider/SecurityUserFactoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SecurityUserFactoryTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionIfUserNotFound(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User not found for UUID:');

        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn(null);

        (new SecurityUserFactory($userRepository, $rolesService, ''))
            ->loadUserByUsername('test_user');
    }

    /**
     * @throws Throwable
     */
    public function testThatLoadByUsernameReturnsExpectedSecurityUser(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn($user);

        $rolesService
            ->expects(static::once())
            ->method('getInheritedRoles')
            ->with($user->getRoles())
            ->willReturn(['FOO', 'BAR']);

        $securityUser = (new SecurityUserFactory($userRepository, $rolesService, ''))
            ->loadUserByUsername('test_user');

        static::assertSame($user->getId(), $securityUser->getUsername());
        static::assertSame(['FOO', 'BAR'], $securityUser->getRoles());
    }

    /**
     * @dataProvider dataProviderTestThatSupportsMethodsReturnsFalseWithNotSupportedType
     *
     * @param mixed $input
     */
    public function testThatSupportsMethodsReturnsFalseWithNotSupportedType($input): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        static::assertFalse(
            (new SecurityUserFactory($userRepository, $rolesService, ''))->supportsClass((string)$input)
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatSupportsMethodsReturnsTrueWithSupportedType(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        static::assertTrue(
            (new SecurityUserFactory($userRepository, $rolesService, ''))->supportsClass(SecurityUser::class)
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnExceptionWithNotSupportedUser(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Invalid user class "Symfony\Component\Security\Core\User\User"');

        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new SecurityUserFactory($userRepository, $rolesService, ''))
            ->refreshUser(new CoreUser('test_user', 'password'));
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserNotFound(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User not found for UUID:');

        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new SecurityUserFactory($userRepository, $rolesService, ''))
            ->refreshUser(new SecurityUser(new User()));
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserReturnsNewInstanceOfSecurityUser(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User();
        $securityUser = new SecurityUser($user, ['FOO', 'BAR']);

        $userRepository
            ->expects(static::once())
            ->method('find')
            ->with($securityUser->getUsername())
            ->willReturn($user);

        $rolesService
            ->expects(static::once())
            ->method('getInheritedRoles')
            ->with($user->getRoles())
            ->willReturn(['FOO', 'BAR']);

        $newSecurityUser = (new SecurityUserFactory($userRepository, $rolesService, ''))
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
}
