<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/SecurityUserFactoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\RolesService;
use App\Security\SecurityUser;
use App\Security\SecurityUserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class SecurityUserFactoryTest
 *
 * @package App\Tests\Unit\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SecurityUserFactoryTest extends KernelTestCase
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage User not found for UUID:
     *
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionIfUserNotFound(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService   $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn(null);

        $factory = new SecurityUserFactory($userRepository, $rolesService);

        $factory->loadUserByUsername('test_user');
    }

    /**
     * @throws Throwable
     */
    public function testThatLoadByUsernameReturnsExpectedSecurityUser():  void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService   $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

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

        $securityUser = (new SecurityUserFactory($userRepository, $rolesService))->loadUserByUsername('test_user');

        static::assertSame($user->getId(), $securityUser->getUsername());
        static::assertSame(['FOO', 'BAR'], $securityUser->getRoles());
    }

    public function testThatSupportsMethodsReturnsFalseWithNotSupportedType(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService   $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        static::assertFalse((new SecurityUserFactory($userRepository, $rolesService))->supportsClass(null));
    }

    public function testThatSupportsMethodsReturnsTrueWithSupportedType(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService   $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        static::assertTrue(
            (new SecurityUserFactory($userRepository, $rolesService))->supportsClass(SecurityUser::class)
        );
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Invalid user class "Symfony\Component\Security\Core\User\User"
     *
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnExceptionWithNotSupportedUser(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService   $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        (new SecurityUserFactory($userRepository, $rolesService))
            ->refreshUser(new \Symfony\Component\Security\Core\User\User('test_user', 'password'));
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage User not found for UUID:
     *
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserNotFound(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService   $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        (new SecurityUserFactory($userRepository, $rolesService))
            ->refreshUser(new SecurityUser(new User()));
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserReturnsNewInstanceOfSecurityUser(): void
    {
        /**
         * @var MockObject|UserRepository $userRepository
         * @var MockObject|RolesService   $rolesService
         */
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $securityUser = (new SecurityUser($user))->setRoles(['FOO', 'BAR']);

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

        $newSecurityUser = (new SecurityUserFactory($userRepository, $rolesService))
            ->refreshUser($securityUser);

        static::assertNotSame($securityUser, $newSecurityUser);
        static::assertSame($securityUser->getUsername(), $newSecurityUser->getUsername());
        static::assertSame($securityUser->getRoles(), $newSecurityUser->getRoles());
    }
}
