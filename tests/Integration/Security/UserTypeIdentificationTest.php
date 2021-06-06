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
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use Doctrine\ORM\NonUniqueResultException;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Throwable;
use function assert;

/**
 * Class UserTypeIdentificationTest
 *
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserTypeIdentificationTest extends KernelTestCase
{
    private MockObject | TokenStorageInterface | null $tokenStorage = null;
    private MockObject | UserRepository | null $userRepository = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getApiKey` returns null when using `$token` as a token
     */
    public function testThatGetApiKeyReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertNull(
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getApiKey(),
        );
    }

    /**
     * @testdox Test that `getApiKey` returns correct user
     */
    public function testThatGetApiKeyReturnsExpectedApiKey(): void
    {
        $apiKey = new ApiKey();
        $apiKeyUser = new ApiKeyUser($apiKey, []);

        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $apiKey,
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getApiKey(),
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
        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertNull(
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getUser(),
        );
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getUser` returns correct user
     */
    public function testThatGetUserReturnsExpectedUser(): void
    {
        $user = (new User())->setUsername('some-username');
        $securityUser = new SecurityUser($user);

        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        $this->getUserRepositoryMock()
            ->expects(static::once())
            ->method('loadUserByIdentifier')
            ->with($user->getId(), true)
            ->willReturn($user);

        static::assertSame(
            $user,
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getUser(),
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetIdentityReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getIdentity` returns null when using `$token` as a token
     */
    public function testThatGetIdentityReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->getTokenStorageMock()
            ->expects(static::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        static::assertNull(
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getIdentity(),
        );
    }

    /**
     * @testdox Test that `getIdentity` returns correct `SecurityUser` instance
     */
    public function testThatGetIdentityReturnsExpectedSecurityUser(): void
    {
        $securityUser = new SecurityUser(new User());

        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $securityUser,
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getIdentity()
        );
    }

    /**
     * @testdox Test that `getIdentity` returns correct `ApiKeyUser` instance
     */
    public function testThatGetIdentityReturnsExpectedApiKeyUser(): void
    {
        $apiKeyUser = new ApiKeyUser(new ApiKey(), []);

        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $this->getTokenStorageMock()
            ->expects(static::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $apiKeyUser,
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getIdentity()
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyUserReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getApiKeyUser` returns null when using `$token` as a token
     */
    public function testThatGetApiKeyUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertNull(
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getApiKeyUser(),
        );
    }

    /**
     * @testdox Test that `getApiKeyUser` returns correct user
     */
    public function testThatGetApiKeyUserReturnsExpectedUser(): void
    {
        $apiKeyUser = new ApiKeyUser(new ApiKey(), []);

        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $apiKeyUser,
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getApiKeyUser(),
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetSecurityUserReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getSecurityUser` returns null when using `$token` as a token
     */
    public function testThatGetSecurityUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertNull(
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getSecurityUser(),
        );
    }

    /**
     * @testdox Test that `getSecurityUser` returns correct user
     */
    public function testThatGetSecurityUserReturnsExpectedUser(): void
    {
        $securityUser = new SecurityUser(new User());

        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $this->getTokenStorageMock()
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $securityUser,
            (new UserTypeIdentification($this->getTokenStorage(), $this->getUserRepository()))->getSecurityUser(),
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

    private function getTokenStorage(): TokenStorageInterface
    {
        assert($this->tokenStorage instanceof TokenStorageInterface);

        return $this->tokenStorage;
    }

    private function getTokenStorageMock(): MockObject
    {
        assert($this->tokenStorage instanceof MockObject);

        return $this->tokenStorage;
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
}
