<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Security/Provider/SecurityUserFactoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Throwable;

/**
 * Class SecurityUserFactoryTest
 *
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class SecurityUserFactoryTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatLoadUserByIdentifierThrowsAnExceptionWithInvalidUsername(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->getSecurityUserFactory()->loadUserByIdentifier('foobar');
    }

    /**
     * @dataProvider dataProviderTestThatLoadUserByIdentifierReturnsExpectedUserInstance
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $roles
     * @psalm-param StringableArrayObject $roles
     *
     * @throws Throwable
     *
     * @testdox Test that `loadUserByIdentifier` method with `$username` input returns `SecurityUser` with `$roles` roles.
     */
    public function testThatLoadUserByIdentifierReturnsExpectedUserInstance(
        string $username,
        StringableArrayObject $roles
    ): void {
        $domainUser = $this->getSecurityUserFactory()->loadUserByIdentifier($username);

        static::assertInstanceOf(SecurityUser::class, $domainUser);
        static::assertSame($roles->getArrayCopy(), $domainUser->getRoles());
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserIsNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->getSecurityUserFactory()->refreshUser(new SecurityUser(new User()));
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserReturnsCorrectUser(): void
    {
        $user = $this->getUserRepository()->findOneBy(['username' => 'john']);

        static::assertNotNull($user);
        static::assertInstanceOf(User::class, $user);

        $securityUser = new SecurityUser($user);

        static::assertSame(
            $user->getId(),
            $this->getSecurityUserFactory()->refreshUser($securityUser)->getUserIdentifier()
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserReturnsANewInstanceOfSecurityUser(): void
    {
        $user = $this->getUserRepository()->findOneBy(['username' => 'john']);

        static::assertNotNull($user);
        static::assertInstanceOf(User::class, $user);

        $securityUser = new SecurityUser($user);

        static::assertNotSame($securityUser, $this->getSecurityUserFactory()->refreshUser($securityUser));
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserClassIsNotSupported(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectErrorMessageMatches('#^Invalid user class(.*)#');

        $user = new InMemoryUser('username', 'password');

        $this->getSecurityUserFactory()->refreshUser($user);
    }

    /**
     * @return Generator<array{0: string, 1: StringableArrayObject}>
     */
    public function dataProviderTestThatLoadUserByIdentifierReturnsExpectedUserInstance(): Generator
    {
        yield ['john', new StringableArrayObject([])];
        yield ['john-api', new StringableArrayObject(['ROLE_API', 'ROLE_LOGGED'])];
        yield ['john-logged', new StringableArrayObject(['ROLE_LOGGED'])];
        yield ['john-user', new StringableArrayObject(['ROLE_USER', 'ROLE_LOGGED'])];
        yield ['john-admin', new StringableArrayObject(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED'])];
        yield ['john-root', new StringableArrayObject(['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED'])];
    }

    private function getSecurityUserFactory(): SecurityUserFactory
    {
        /** @var SecurityUserFactory $securityUserFactory */
        $securityUserFactory = static::getContainer()->get(SecurityUserFactory::class);

        return $securityUserFactory;
    }

    private function getUserRepository(): UserRepository
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return $userRepository;
    }
}
