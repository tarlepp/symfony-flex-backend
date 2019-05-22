<?php
declare(strict_types=1);
/**
 * /tests/Integration/Utils/ApiKeyAuthenticatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Security;

use App\Security\ApiKeyAuthenticator;
use App\Security\ApiKeyUserProvider;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

/**
 * Class ApiKeyAuthenticatorTest
 *
 * @package App\Tests\Integration\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyAuthenticatorTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatCreateTokenReturnsExpected
     *
     * @param Request $request
     * @param string  $providerKey
     * @param mixed   $expected
     */
    public function testThatCreateTokenReturnsExpected(Request $request, string $providerKey, $expected): void
    {
        $apiKeyAuthenticator = new ApiKeyAuthenticator();

        static::assertEquals($expected, $apiKeyAuthenticator->createToken($request, $providerKey));

        unset($apiKeyAuthenticator);
    }

    /**
     * @dataProvider dataProviderTestThatSupportsTokenReturnsExpected
     *
     * @param array $args
     * @param bool  $expected
     */
    public function testThatSupportsTokenReturnsExpected(array $args, bool $expected): void
    {
        $apiKeyAuthenticator = new ApiKeyAuthenticator();

        static::assertEquals($expected, $apiKeyAuthenticator->supportsToken(...$args));

        unset($apiKeyAuthenticator);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /User provider must be instance of '.*' class/
     *
     * @throws Throwable
     */
    public function testThatAuthenticateTokenThrowsAnExceptionIfChainUserProviderNotProvided(): void
    {
        /**
         * @var MockObject|TokenInterface        $token
         * @var MockObject|UserProviderInterface $userProvider
         */
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();

        $apiKeyAuthenticator = new ApiKeyAuthenticator();
        $apiKeyAuthenticator->authenticateToken($token, $userProvider, 'providerKey');

        unset($apiKeyAuthenticator, $userProvider, $token);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage The user provider must be an instance of ApiKeyUserProvider
     *
     * @throws Throwable
     */
    public function testThatAuthenticateTokenThrowsAnExceptionIfApiKeyUserProviderIsNotPresent(): void
    {
        /**
         * @var MockObject|TokenInterface        $token
         * @var MockObject|UserProviderInterface $userProvider
         * @var MockObject|ChainUserProvider     $chainProvider
         */
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
        $chainProvider = $this->getMockBuilder(ChainUserProvider::class)->disableOriginalConstructor()->getMock();

        $chainProvider
            ->expects(static::once())
            ->method('getProviders')
            ->willReturn([$userProvider]);

        $apiKeyAuthenticator = new ApiKeyAuthenticator();
        $apiKeyAuthenticator->authenticateToken($token, $chainProvider, 'providerKey');

        unset($apiKeyAuthenticator, $chainProvider, $userProvider, $token);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException
     * @expectedExceptionMessage Invalid API key
     *
     * @throws Throwable
     */
    public function testThatAuthenticateTokenThrowsAnExceptionWhenApiKeyIsNotFound(): void
    {
        /**
         * @var MockObject|TokenInterface     $token
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         * @var MockObject|ChainUserProvider  $chainProvider
         */
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)->disableOriginalConstructor()->getMock();
        $chainProvider = $this->getMockBuilder(ChainUserProvider::class)->disableOriginalConstructor()->getMock();

        $chainProvider
            ->expects(static::once())
            ->method('getProviders')
            ->willReturn([$apiKeyUserProvider]);

        $token
            ->expects(static::once())
            ->method('getCredentials')
            ->willReturn('some_api_token');

        $apiKeyUserProvider
            ->expects(static::once())
            ->method('getApiKeyForToken')
            ->with('some_api_token')
            ->willReturn(null);

        $apiKeyAuthenticator = new ApiKeyAuthenticator();
        $apiKeyAuthenticator->authenticateToken($token, $chainProvider, 'providerKey');

        unset($apiKeyAuthenticator, $apiKeyUserProvider, $token, $chainProvider);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatCreateTokenReturnsExpected(): Generator
    {
        yield [
            new Request(),
            'api',
            null,
        ];

        yield [
            new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer token']),
            'api',
            null,
        ];

        yield [
            new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'ApiKey']),
            'api',
            null,
        ];

        yield [
            new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'ApiKey some_token']),
            'api',
            new PreAuthenticatedToken('anon', 'some_token', 'api'),
        ];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatSupportsTokenReturnsExpected(): Generator
    {
        yield [
            [
                new AnonymousToken('secret', 'user'),
                'someProvider',
            ],
            false,
        ];

        yield [
            [
                new PreAuthenticatedToken('user', 'credentials', 'providerKey'),
                'notValidProviderKey',
            ],
            false,
        ];

        yield [
            [
                new PreAuthenticatedToken('user', 'credentials', 'providerKey'),
                'providerKey',
            ],
            true,
        ];
    }
}
