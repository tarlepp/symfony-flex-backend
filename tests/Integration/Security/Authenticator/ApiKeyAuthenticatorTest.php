<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Authenticator/ApiKeyAuthenticatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security\Authenticator;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Security\Authenticator\ApiKeyAuthenticator;
use App\Security\Provider\ApiKeyUserProvider;
use App\Security\SecurityUser;
use App\Tests\Utils\StringableArrayObject;
use App\Utils\JSON;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Throwable;
use function property_exists;

/**
 * @package App\Tests\Integration\Security\Authenticator
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class ApiKeyAuthenticatorTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSupportReturnsExpected')]
    #[TestDox('Test that `supports` method returns `$expected` for request `$request`')]
    public function testThatSupportReturnsExpected(bool $expected, Request $request): void
    {
        $apiKeyUserProviderMock = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new ApiKeyAuthenticator($apiKeyUserProviderMock);

        self::assertSame($expected, $authenticator->supports($request));
    }

    #[TestDox('Test that `authenticate` method returns expected `passport` which have `UserBadge` set')]
    public function testThatAuthenticateMethodCallsExpectedServiceMethodAndReturnsExpectedBadge(): void
    {
        $apiKey = new ApiKey();

        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyUserProvider
            ->expects($this->once())
            ->method('getApiKeyForToken')
            ->with('SomeToken')
            ->willReturn($apiKey);

        $request = new Request();
        $request->headers = new HeaderBag([
            'Authorization' => 'ApiKey SomeToken',
        ]);

        $passport = new ApiKeyAuthenticator($apiKeyUserProvider)->authenticate($request);

        self::assertTrue($passport->hasBadge(UserBadge::class));

        $badge = $passport->getBadge(UserBadge::class);

        self::assertNotNull($badge);
        self::assertTrue($badge->isResolved());
    }

    #[TestDox('Test that `authenticate` method throws an exception if API key is not found')]
    public function testThatAuthenticateMethodThrowsAnExceptionWhenTokenNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('API key not found');

        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        new ApiKeyAuthenticator($apiKeyUserProvider)->authenticate(new Request());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `onAuthenticationSuccess` method return `null` when using anonymous token')]
    public function testThatOnAuthenticationSuccessReturnsNull(): void
    {
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new ApiKeyAuthenticator($apiKeyUserProvider);

        self::assertNull($authenticator->onAuthenticationSuccess(
            new Request(),
            new UsernamePasswordToken(new SecurityUser(new User()), 'foobar'),
            'foobar',
        ));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `onAuthenticationFailure` method returns expected response')]
    public function testThatOnAuthenticationFailureMethodReturnsExpectedResponse(): void
    {
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new ApiKeyAuthenticator($apiKeyUserProvider);

        $response = $authenticator->onAuthenticationFailure(new Request(), new AuthenticationException());

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());

        $content = $response->getContent();

        self::assertIsString($content);
        self::assertJson($content);

        $decoded = JSON::decode($content);

        self::assertIsObject($decoded);
        self::assertTrue(property_exists($decoded, 'message'));
        self::assertSame('Invalid API key', $decoded->message);
    }

    /**
     * @return Generator<array{0: boolean, 1: Request}>
     */
    public static function dataProviderTestThatSupportReturnsExpected(): Generator
    {
        yield [false, new Request()];

        $request = new Request();
        $request->headers = new HeaderBag([
            'Authorization' => 'ApiKey',
        ]);

        yield [false, $request];

        $request = new Request();
        $request->headers = new HeaderBag([
            'Authorization' => 'ApiKey somekey',
        ]);

        yield [true, $request];
    }

    /**
     * @psalm-return Generator<array{0: null|StringableArrayObject, 1: Request}>
     * @phpstan-return Generator<array{0: null|StringableArrayObject<mixed>, 1: Request}>
     */
    public static function dataProviderTestThatGetCredentialsReturnsExpected(): Generator
    {
        yield [null, new Request()];

        $request = new Request();
        $request->headers = new HeaderBag([
            'Authorization' => 'FooBar',
        ]);

        yield [null, $request];

        $request = new Request();
        $request->headers = new HeaderBag([
            'Authorization' => 'ApiKey',
        ]);

        yield [null, $request];

        $request = new Request();
        $request->headers = new HeaderBag([
            'Authorization' => 'ApiKey    ',
        ]);

        yield [null, $request];

        $request = new Request();
        $request->headers = new HeaderBag([
            'Authorization' => 'ApiKey somekey',
        ]);

        yield [new StringableArrayObject([
            'token' => 'somekey',
        ]), $request];
    }

    /**
     * @psalm-return Generator<array{0: string|int|stdClass|StringableArrayObject|null}>
     * @phpstan-return Generator<array{0: string|int|stdClass|StringableArrayObject<mixed>|null}>
     */
    public static function dataProviderTestThatGetUserReturnsExpected(): Generator
    {
        yield [null];
        yield ['foobar'];
        yield [123];
        yield [new stdClass()];
        yield [new StringableArrayObject([])];
        yield [new StringableArrayObject(['foobar'])];
        yield [
            new StringableArrayObject([
                'foobar' => 'barfoo',
            ]),
        ];
        yield [
            new StringableArrayObject([
                'token' => null,
            ]),
        ];
    }

    /**
     * @psalm-return Generator<array{0: string|int|stdClass|StringableArrayObject|null}>
     * @phpstan-return Generator<array{0: string|int|stdClass|StringableArrayObject<mixed>|null}>
     */
    public static function dataProviderTestThatCheckCredentialsThrowsAnException(): Generator
    {
        yield [null];
        yield ['foobar'];
        yield [123];
        yield [new stdClass()];
        yield [new StringableArrayObject([])];
        yield [new StringableArrayObject(['foobar'])];
        yield [
            new StringableArrayObject([
                'foobar' => 'barfoo',
            ]),
        ];
        yield [
            new StringableArrayObject([
                'token' => null,
            ]),
        ];
    }
}
