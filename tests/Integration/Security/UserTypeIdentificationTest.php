<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/UserTypeIdentificationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security;

use App\Entity\ApiKey;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
use App\Security\RolesService;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use Doctrine\ORM\NonUniqueResultException;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Throwable;

/**
 * Class UserTypeIdentificationTest
 *
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserTypeIdentificationTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getApiKey` returns null when using `$token` as a token
     */
    public function testThatGetApiKeyReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getApiKey(),
        );
    }

    /**
     * @testdox Test that `getApiKey` returns correct user
     */
    public function testThatGetApiKeyReturnsExpectedApiKey(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $roleServiceMock = $this->createMock(RolesService::class);

        $roleServiceMock
            ->expects(self::once())
            ->method('getInheritedRoles')
            ->willReturn([
               'ROLE_API',
               'Some Role',
            ]);

        $apiKeyEntity = (new ApiKey())->addUserGroup((new UserGroup())->setRole(new Role('Some Role')));
        $apiKeyUser = new ApiKeyUser($apiKeyEntity, $roleServiceMock);
        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $apiKey = (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getApiKey();

        self::assertNotNull($apiKey);

        self::assertSame(
            $apiKeyEntity,
            $apiKey,
        );

        self::assertSame(
            ['ROLE_API', 'Some Role'],
            $apiKeyUser->getRoles(),
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetUserReturnsNullWhenTokenIsNotValid
     *
     * @throws NonUniqueResultException
     *
     * @testdox Test that `getUser` returns null when using `$token` as a token
     */
    public function testThatGetUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getUser(),
        );
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getUser` returns correct user
     */
    public function testThatGetUserReturnsExpectedUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);

        $user = (new User())->setUsername('some-username');
        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $userRepositoryMock
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($user->getId(), true)
            ->willReturn($user);

        self::assertSame(
            $user,
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getUser(),
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetIdentityReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getIdentity` returns null when using `$token` as a token
     */
    public function testThatGetIdentityReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);

        $tokenStorageMock
            ->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getIdentity(),
        );
    }

    /**
     * @testdox Test that `getIdentity` returns correct `SecurityUser` instance
     */
    public function testThatGetIdentityReturnsExpectedSecurityUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);

        $securityUser = new SecurityUser(new User());
        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            $securityUser,
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getIdentity()
        );
    }

    /**
     * @testdox Test that `getIdentity` returns correct `ApiKeyUser` instance
     */
    public function testThatGetIdentityReturnsExpectedApiKeyUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $roleServiceMock = $this->createMock(RolesService::class);

        $apiKeyUser = new ApiKeyUser(new ApiKey(), $roleServiceMock);
        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $tokenStorageMock
            ->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            $apiKeyUser,
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getIdentity()
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyUserReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getApiKeyUser` returns null when using `$token` as a token
     */
    public function testThatGetApiKeyUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getApiKeyUser(),
        );
    }

    /**
     * @testdox Test that `getApiKeyUser` returns correct user
     */
    public function testThatGetApiKeyUserReturnsExpectedUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $roleServiceMock = $this->createMock(RolesService::class);

        $apiKeyUser = new ApiKeyUser(new ApiKey(), $roleServiceMock);
        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            $apiKeyUser,
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getApiKeyUser(),
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetSecurityUserReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getSecurityUser` returns null when using `$token` as a token
     */
    public function testThatGetSecurityUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getSecurityUser(),
        );
    }

    /**
     * @testdox Test that `getSecurityUser` returns correct user
     */
    public function testThatGetSecurityUserReturnsExpectedUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);

        $securityUser = new SecurityUser(new User());
        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            $securityUser,
            (new UserTypeIdentification($tokenStorageMock, $userRepositoryMock))->getSecurityUser(),
        );
    }

    /**
     * @return Generator<array{0: \Symfony\Component\Security\Core\Authentication\Token\AbstractToken|null}>
     */
    public function dataProviderTestThatGetUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    /**
     * @return Generator<array{0: \Symfony\Component\Security\Core\Authentication\Token\AbstractToken|null}>
     */
    public function dataProviderTestThatGetApiKeyReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    /**
     * @return Generator<array{0: \Symfony\Component\Security\Core\Authentication\Token\AbstractToken|null}>
     */
    public function dataProviderTestThatGetSecurityUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    /**
     * @return Generator<array{0: \Symfony\Component\Security\Core\Authentication\Token\AbstractToken|null}>
     */
    public function dataProviderTestThatGetApiKeyUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    /**
     * @return Generator<array{0: \Symfony\Component\Security\Core\Authentication\Token\AbstractToken|null}>
     */
    public function dataProviderTestThatGetIdentityReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    /**
     * @return Generator<array{0: \Symfony\Component\Security\Core\Authentication\Token\AbstractToken|null}>
     */
    private function getInvalidTokens(): Generator
    {
        yield [null];

        yield [new AnonymousToken('secret', 'user')];

        yield [new AnonymousToken(
            'secret',
            new InMemoryUser('username', 'password'),
        )];

        yield [new UsernamePasswordToken('user', 'credentials', 'providerKey')];

        yield [new UsernamePasswordToken(
            new InMemoryUser('username', 'password'),
            'credentials',
            'providerKey',
        )];

        yield [new PreAuthenticatedToken('user', 'credentials', 'providerKey')];

        yield [new PreAuthenticatedToken(
            new InMemoryUser('username', 'password'),
            'credentials',
            'providerKey',
        )];

        yield [new RememberMeToken(
            new InMemoryUser('username', 'password'),
            'provider-key',
            'some-secret',
        )];
    }
}
