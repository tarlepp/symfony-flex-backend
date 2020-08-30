<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/UserTypeIdentificationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
use Symfony\Component\Security\Core\User\User as CoreUser;
use Throwable;

/**
 * Class UserTypeIdentificationTest
 *
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserTypeIdentificationTest extends KernelTestCase
{
    /**
     * @var MockObject|TokenStorageInterface
     */
    private MockObject $tokenStorage;

    /**
     * @var MockObject|UserRepository
     */
    private MockObject $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getApiKey` returns null when using `$token` as a token.
     */
    public function testThatGetApiKeyReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertNull((new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getApiKey());
    }

    /**
     * @testdox Test that `getApiKey` returns correct user.
     */
    public function testThatGetApiKeyReturnsExpectedApiKey(): void
    {
        $apiKey = new ApiKey();
        $apiKeyUser = new ApiKeyUser($apiKey, []);

        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $apiKey,
            (new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getApiKey()
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetUserReturnsNullWhenTokenIsNotValid
     *
     * @throws NonUniqueResultException
     *
     * @testdox Test that `getUser` returns null when using `$token` as a token.
     */
    public function testThatGetUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertNull((new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getUser());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getUser` returns correct user.
     */
    public function testThatGetUserReturnsExpectedUser(): void
    {
        $user = (new User())->setUsername('some-username');
        $securityUser = new SecurityUser($user);

        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        $this->userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with($user->getId(), true)
            ->willReturn($user);

        static::assertSame($user, (new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getUser());
    }

    /**
     * @dataProvider dataProviderTestThatGetIdentityReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getIdentity` returns null when using `$token` as a token.
     */
    public function testThatGetIdentityReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects(static::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        static::assertNull((new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getIdentity());
    }

    /**
     * @testdox Test that `getIdentity` returns correct SecurityUser.
     */
    public function testThatGetIdentityReturnsExpectedSecurityUser(): void
    {
        $securityUser = new SecurityUser(new User());

        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $securityUser,
            (new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getIdentity()
        );
    }

    /**
     * @testdox Test that `getIdentity` returns correct ApiKeyUser.
     */
    public function testThatGetIdentityReturnsExpectedApiKeyUser(): void
    {
        $apiKeyUser = new ApiKeyUser(new ApiKey(), []);

        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $this->tokenStorage
            ->expects(static::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $apiKeyUser,
            (new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getIdentity()
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyUserReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getApiKeyUser` returns null when using `$token` as a token.
     */
    public function testThatGetApiKeyUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertNull((new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getApiKeyUser());
    }

    /**
     * @testdox Test that `getApiKeyUser` returns correct user.
     */
    public function testThatGetApiKeyUserReturnsExpectedUser(): void
    {
        $apiKeyUser = new ApiKeyUser(new ApiKey(), []);

        $token = new UsernamePasswordToken($apiKeyUser, 'credentials', 'providerKey');

        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $apiKeyUser,
            (new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getApiKeyUser()
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetSecurityUserReturnsNullWhenTokenIsNotValid
     *
     * @testdox Test that `getSecurityUser` returns null when using `$token` as a token.
     */
    public function testThatGetSecurityUserReturnsNullWhenTokenIsNotValid(?TokenInterface $token): void
    {
        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertNull((new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getSecurityUser());
    }

    /**
     * @testdox Test that `getSecurityUser` returns correct user.
     */
    public function testThatGetSecurityUserReturnsExpectedUser(): void
    {
        $securityUser = new SecurityUser(new User());

        $token = new UsernamePasswordToken($securityUser, 'credentials', 'providerKey');

        $this->tokenStorage
            ->expects(static::once())
            ->method('getToken')
            ->willReturn($token);

        static::assertSame(
            $securityUser,
            (new UserTypeIdentification($this->tokenStorage, $this->userRepository))->getSecurityUser()
        );
    }

    public function dataProviderTestThatGetUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    public function dataProviderTestThatGetApiKeyReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    public function dataProviderTestThatGetSecurityUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    public function dataProviderTestThatGetApiKeyUserReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    public function dataProviderTestThatGetIdentityReturnsNullWhenTokenIsNotValid(): Generator
    {
        return $this->getInvalidTokens();
    }

    private function getInvalidTokens(): Generator
    {
        yield [null];

        yield [new AnonymousToken('secret', 'user')];

        yield [new AnonymousToken('secret', new CoreUser('username', 'password'))];

        yield [new UsernamePasswordToken('user', 'credentials', 'providerKey')];

        yield [new UsernamePasswordToken(new CoreUser('username', 'password'), 'credentials', 'providerKey')];

        yield [new PreAuthenticatedToken('user', 'credentials', 'providerKey')];

        yield [new PreAuthenticatedToken(new CoreUser('username', 'password'), 'credentials', 'providerKey')];

        yield [new RememberMeToken(new CoreUser('username', 'password'), 'provider-key', 'some-secret')];
    }
}
