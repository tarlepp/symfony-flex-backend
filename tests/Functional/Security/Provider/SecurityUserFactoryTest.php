<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Security/Provider/SecurityUserFactoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Functional\Security\Provider;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Provider\SecurityUserFactory;
use App\Security\SecurityUser;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User as CoreUser;
use Throwable;

/**
 * Class SecurityUserFactoryTest
 *
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SecurityUserFactoryTest extends KernelTestCase
{
    private SecurityUserFactory $securityUserFactory;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->securityUserFactory = static::$container->get(SecurityUserFactory::class);

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->userRepository = static::$container->get(UserRepository::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionWithInvalidUsername(): void
    {
        $this->expectException(UsernameNotFoundException::class);

        static::assertNull($this->securityUserFactory->loadUserByUsername('foobar'));
    }

    /**
     * @dataProvider dataProviderTestThatLoadUserByUsernameReturnsExpectedUserInstance
     *
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` method with `$username` input returns `SecurityUser` with `$roles` roles.
     */
    public function testThatLoadUserByUsernameReturnsExpectedUserInstance(
        string $username,
        StringableArrayObject $roles
    ): void {
        $domainUser = $this->securityUserFactory->loadUserByUsername($username);

        static::assertInstanceOf(SecurityUser::class, $domainUser);
        static::assertSame($roles->getArrayCopy(), $domainUser->getRoles());
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserIsNotFound(): void
    {
        $this->expectException(UsernameNotFoundException::class);

        $this->securityUserFactory->refreshUser(new SecurityUser(new User()));
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserReturnsCorrectUser(): void
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['username' => 'john']);

        static::assertNotNull($user);
        static::assertInstanceOf(User::class, $user);

        $securityUser = new SecurityUser($user);

        static::assertSame($user->getId(), $this->securityUserFactory->refreshUser($securityUser)->getUsername());
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserReturnsANewInstanceOfSecurityUser(): void
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['username' => 'john']);

        static::assertNotNull($user);
        static::assertInstanceOf(User::class, $user);

        $securityUser = new SecurityUser($user);

        static::assertNotSame($securityUser, $this->securityUserFactory->refreshUser($securityUser));
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserClassIsNotSupported(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Invalid user class "Symfony\Component\Security\Core\User\User"');

        $user = new CoreUser('test', 'password');

        $this->securityUserFactory->refreshUser($user);
    }

    public function dataProviderTestThatLoadUserByUsernameReturnsExpectedUserInstance(): Generator
    {
        yield ['john', new StringableArrayObject([])];
        yield ['john-api', new StringableArrayObject(['ROLE_API', 'ROLE_LOGGED'])];
        yield ['john-logged', new StringableArrayObject(['ROLE_LOGGED'])];
        yield ['john-user', new StringableArrayObject(['ROLE_USER', 'ROLE_LOGGED'])];
        yield ['john-admin', new StringableArrayObject(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED'])];
        yield ['john-root', new StringableArrayObject(['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED'])];
    }
}
