<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/UserTypeIdentificationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
use App\Security\Provider\ApiKeyUserProvider;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Throwable;

/**
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserTypeIdentificationTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetApiKeyReturnsNullWhenTokenIsNotValid')]
    #[TestDox('Test that `getApiKey` returns null when using `$token` as a token')]
    public function testThatGetApiKeyReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            new UserTypeIdentification($tokenStorageMock, $userRepositoryMock, $apiKeyUserProviderMock)->getApiKey(),
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getApiKey` returns correct user')]
    public function testThatGetApiKeyReturnsExpectedApiKey(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $apiKey = new ApiKey();
        $apiKeyUser = new ApiKeyUser($apiKey, []);
        $token = new UsernamePasswordToken($apiKeyUser, 'firewallName', ['role']);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $apiKeyUserProviderMock
            ->expects($this->once())
            ->method('getApiKeyForToken')
            ->with($apiKeyUser->getUserIdentifier())
            ->willReturn($apiKey);

        self::assertSame(
            $apiKey,
            new UserTypeIdentification($tokenStorageMock, $userRepositoryMock, $apiKeyUserProviderMock)->getApiKey(),
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetUserReturnsNullWhenTokenIsNotValid')]
    #[TestDox('Test that `getUser` returns null when using `$token` as a token')]
    public function testThatGetUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            new UserTypeIdentification($tokenStorageMock, $userRepositoryMock, $apiKeyUserProviderMock)->getUser(),
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getUser` returns correct user')]
    public function testThatGetUserReturnsExpectedUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $user = new User()->setUsername('some-username');
        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'firewallName', ['role']);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $userRepositoryMock
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with($user->getId(), true)
            ->willReturn($user);

        self::assertSame(
            $user,
            new UserTypeIdentification($tokenStorageMock, $userRepositoryMock, $apiKeyUserProviderMock)->getUser(),
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetIdentityReturnsNullWhenTokenIsNotValid')]
    #[TestDox('Test that `getIdentity` returns null when using `$token` as a token')]
    public function testThatGetIdentityReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $tokenStorageMock
            ->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            new UserTypeIdentification(
                $tokenStorageMock,
                $userRepositoryMock,
                $apiKeyUserProviderMock
            )->getIdentity(),
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getIdentity` returns correct `SecurityUser` instance')]
    public function testThatGetIdentityReturnsExpectedSecurityUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $securityUser = new SecurityUser(new User());
        $token = new UsernamePasswordToken($securityUser, 'firewallName', ['role']);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            $securityUser,
            new UserTypeIdentification($tokenStorageMock, $userRepositoryMock, $apiKeyUserProviderMock)->getIdentity()
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getIdentity` returns correct `ApiKeyUser` instance')]
    public function testThatGetIdentityReturnsExpectedApiKeyUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $apiKeyUser = new ApiKeyUser(new ApiKey(), []);
        $token = new UsernamePasswordToken($apiKeyUser, 'firewallName', ['role']);

        $tokenStorageMock
            ->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            $apiKeyUser,
            new UserTypeIdentification($tokenStorageMock, $userRepositoryMock, $apiKeyUserProviderMock)->getIdentity()
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetApiKeyUserReturnsNullWhenTokenIsNotValid')]
    #[TestDox('Test that `getApiKeyUser` returns null when using `$token` as a token')]
    public function testThatGetApiKeyUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            new UserTypeIdentification(
                $tokenStorageMock,
                $userRepositoryMock,
                $apiKeyUserProviderMock
            )->getApiKeyUser(),
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getApiKeyUser` returns correct user')]
    public function testThatGetApiKeyUserReturnsExpectedUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $apiKeyUser = new ApiKeyUser(new ApiKey(), []);
        $token = new UsernamePasswordToken($apiKeyUser, 'firewallName', ['role']);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            $apiKeyUser,
            new UserTypeIdentification(
                $tokenStorageMock,
                $userRepositoryMock,
                $apiKeyUserProviderMock
            )->getApiKeyUser(),
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetSecurityUserReturnsNullWhenTokenIsNotValid')]
    #[TestDox('Test that `getSecurityUser` returns null when using `$token` as a token')]
    public function testThatGetSecurityUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull(
            new UserTypeIdentification(
                $tokenStorageMock,
                $userRepositoryMock,
                $apiKeyUserProviderMock,
            )->getSecurityUser(),
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getSecurityUser` returns correct user')]
    public function testThatGetSecurityUserReturnsExpectedUser(): void
    {
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $apiKeyUserProviderMock = $this->createMock(ApiKeyUserProvider::class);

        $securityUser = new SecurityUser(new User());
        $token = new UsernamePasswordToken($securityUser, 'firewallName', ['role']);

        $tokenStorageMock
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(
            $securityUser,
            new UserTypeIdentification(
                $tokenStorageMock,
                $userRepositoryMock,
                $apiKeyUserProviderMock,
            )->getSecurityUser(),
        );
    }

    /**
     * @return Generator<array{0: AbstractToken|null}>
     */
    public static function dataProviderTestThatGetUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return self::getInvalidTokens();
    }

    /**
     * @return Generator<array{0: AbstractToken|null}>
     */
    public static function dataProviderTestThatGetApiKeyReturnsNullWhenTokenIsNotValid(): Generator
    {
        return self::getInvalidTokens();
    }

    /**
     * @return Generator<array{0: AbstractToken|null}>
     */
    public static function dataProviderTestThatGetSecurityUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return self::getInvalidTokens();
    }

    /**
     * @return Generator<array{0: AbstractToken|null}>
     */
    public static function dataProviderTestThatGetApiKeyUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return self::getInvalidTokens();
    }

    /**
     * @return Generator<array{0: AbstractToken|null}>
     */
    public static function dataProviderTestThatGetIdentityReturnsNullWhenTokenIsNotValid(): Generator
    {
        return self::getInvalidTokens();
    }

    /**
     * @return Generator<array{0: AbstractToken|null}>
     */
    private static function getInvalidTokens(): Generator
    {
        yield [null];

        yield [new UsernamePasswordToken(
            new InMemoryUser('username', 'password'),
            'firewallName',
            ['role']
        )];

        yield [new PreAuthenticatedToken(
            new InMemoryUser('username', 'password'),
            'firewallName',
            ['role'],
        )];

        yield [new RememberMeToken(
            new InMemoryUser('username', 'password'),
            'firewallName',
        )];
    }
}
