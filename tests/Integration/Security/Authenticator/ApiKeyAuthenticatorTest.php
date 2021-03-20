<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Authenticator/ApiKeyAuthenticatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security\Authenticator;

use App\Entity\ApiKey;
use App\Security\Authenticator\ApiKeyAuthenticator;
use App\Security\Provider\ApiKeyUserProvider;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\User;
use Throwable;
use function assert;
use function json_encode;

/**
 * Class ApiKeyAuthenticatorTest
 *
 * @package App\Tests\Integration\Security\Authenticator
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyAuthenticatorTest extends KernelTestCase
{
    private MockObject | ApiKeyUserProvider | null $apiKeyUserProvider = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider dataProviderTestThatSupportReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `supports` method returns `$expected` for request `$request`
     */
    public function testThatSupportReturnsExpected(bool $expected, Request $request): void
    {
        $authenticator = new ApiKeyAuthenticator($this->getApiKeyUserProvider());

        static::assertSame($expected, $authenticator->supports($request));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `start` method returns expected output
     */
    public function testThatStartMethodReturnsExpected(): void
    {
        $authenticator = new ApiKeyAuthenticator($this->getApiKeyUserProvider());

        $output = $authenticator->start(new Request());
        $content = $output->getContent();

        static::assertSame(401, $output->getStatusCode());
        static::assertNotFalse($content);
        static::assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Authentication Required'], JSON_THROW_ON_ERROR),
            $content,
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetCredentialsReturnsExpected
     *
     * @phpstan-param StringableArrayObject<array<mixed>> $expected
     * @psalm-param StringableArrayObject $expected
     *
     * @throws Throwable
     *
     * @testdox Test that `getCredentials` method returns `$expected` when using `$request` as request
     */
    public function testThatGetCredentialsReturnsExpected(?StringableArrayObject $expected, Request $request): void
    {
        $authenticator = new ApiKeyAuthenticator($this->getApiKeyUserProvider());

        static::assertSame(
            $expected === null ? null : $expected->getArrayCopy(),
            $authenticator->getCredentials($request)
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetUserReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `getUser` returns null when using `$credentials` as credentials
     */
    public function testThatGetUserReturnsExpectedWhenCredentialsIsInvalid(mixed $credentials): void
    {
        $authenticator = new ApiKeyAuthenticator($this->getApiKeyUserProvider());

        static::assertNull(
            $authenticator->getUser(
                $credentials instanceof StringableArrayObject ? $credentials->getArrayCopy() : $credentials,
                $this->getApiKeyUserProvider(),
            ),
        );
    }

    /**
     * @dataProvider dataProviderTestThatCheckCredentialsThrowsAnException
     *
     * @phpstan-param StringableArrayObject<array<mixed>> $credentials
     * @psalm-param StringableArrayObject $credentials
     *
     * @throws Throwable
     *
     * @testdox Test that `checkCredentials` method throws `Invalid token` exception when using `$credentials` as input
     */
    public function testThatCheckCredentialsThrowsAnException(
        string | int | stdClass | StringableArrayObject | null $credentials,
    ): void {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token');

        (new ApiKeyAuthenticator($this->getApiKeyUserProvider()))->checkCredentials(
            $credentials instanceof StringableArrayObject ? $credentials->getArrayCopy() : $credentials,
            new User('user', 'password'),
        );
    }

    /**
     * @testdox Test that `checkCredentials` returns `false` when token is not valid
     */
    public function testThatCheckCredentialsReturnsFalseWhenValidToken(): void
    {
        $this->getApiKeyUserProviderMock()
            ->expects(static::once())
            ->method('getApiKeyForToken')
            ->with('some-token')
            ->willReturn(null);

        static::assertFalse(
            (new ApiKeyAuthenticator($this->getApiKeyUserProvider()))
                ->checkCredentials(['token' => 'some-token'], new User('user', 'password')),
        );
    }

    /**
     * @testdox Test that `checkCredentials` returns `true` when token is valid
     */
    public function testThatCheckCredentialsReturnsTrueWhenValidToken(): void
    {
        $apiKey = new ApiKey();

        $this->getApiKeyUserProviderMock()
            ->expects(static::once())
            ->method('getApiKeyForToken')
            ->with('some-token')
            ->willReturn($apiKey);

        static::assertTrue(
            (new ApiKeyAuthenticator($this->getApiKeyUserProvider()))
                ->checkCredentials(['token' => 'some-token'], new User('user', 'password')),
        );
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `onAuthenticationSuccess` return null when using anonymous token
     */
    public function testThatOnAuthenticationSuccessReturnsNull(): void
    {
        $authenticator = new ApiKeyAuthenticator($this->getApiKeyUserProvider());

        static::assertNull($authenticator->onAuthenticationSuccess(
            new Request(),
            new AnonymousToken('secret', 'user'),
            'foobar',
        ));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `onAuthenticationFailure` returns expected output
     */
    public function testThatOnAuthenticationFailureReturnsExpected(): void
    {
        $output = (new ApiKeyAuthenticator($this->getApiKeyUserProvider()))
            ->onAuthenticationFailure(new Request(), new AuthenticationException('foobar'));

        static::assertSame(Response::HTTP_FORBIDDEN, $output->getStatusCode());
        static::assertJsonStringEqualsJsonString(
            json_encode(['message' => 'An authentication exception occurred.'], JSON_THROW_ON_ERROR),
            (string)$output->getContent(),
        );
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `supportsRememberMe` returns always `false`
     */
    public function testThatSupportsRememberMeReturnsFalse(): void
    {
        static::assertFalse((new ApiKeyAuthenticator($this->getApiKeyUserProvider()))->supportsRememberMe());
    }

    /**
     * @return Generator<array{0: boolean, 1: Request}>
     */
    public function dataProviderTestThatSupportReturnsExpected(): Generator
    {
        yield [false, new Request()];

        $request = new Request();
        $request->headers = new HeaderBag(['Authorization' => 'ApiKey']);

        yield [false, $request];

        $request = new Request();
        $request->headers = new HeaderBag(['Authorization' => 'ApiKey somekey']);

        yield [true, $request];
    }

    /**
     * @return Generator<array{0: null|StringableArrayObject, 1: Request}>
     */
    public function dataProviderTestThatGetCredentialsReturnsExpected(): Generator
    {
        yield [null, new Request()];

        $request = new Request();
        $request->headers = new HeaderBag(['Authorization' => 'FooBar']);

        yield [null, $request];

        $request = new Request();
        $request->headers = new HeaderBag(['Authorization' => 'ApiKey']);

        yield [null, $request];

        $request = new Request();
        $request->headers = new HeaderBag(['Authorization' => 'ApiKey    ']);

        yield [null, $request];

        $request = new Request();
        $request->headers = new HeaderBag(['Authorization' => 'ApiKey somekey']);

        yield [new StringableArrayObject(['token' => 'somekey']), $request];
    }

    /**
     * @return Generator<array{0: string|int|stdClass|StringableArrayObject|null}>
     */
    public function dataProviderTestThatGetUserReturnsExpected(): Generator
    {
        yield [null];
        yield ['foobar'];
        yield [123];
        yield [new stdClass()];
        yield [new StringableArrayObject([])];
        yield [new StringableArrayObject(['foobar'])];
        yield [new StringableArrayObject(['foobar' => 'barfoo'])];
        yield [new StringableArrayObject(['token' => null])];
    }

    /**
     * @return Generator<array{0: string|int|stdClass|StringableArrayObject|null}>
     */
    public function dataProviderTestThatCheckCredentialsThrowsAnException(): Generator
    {
        yield [null];
        yield ['foobar'];
        yield [123];
        yield [new stdClass()];
        yield [new StringableArrayObject([])];
        yield [new StringableArrayObject(['foobar'])];
        yield [new StringableArrayObject(['foobar' => 'barfoo'])];
        yield [new StringableArrayObject(['token' => null])];
    }

    private function getApiKeyUserProvider(): ApiKeyUserProvider
    {
        assert($this->apiKeyUserProvider instanceof ApiKeyUserProvider);

        return $this->apiKeyUserProvider;
    }

    private function getApiKeyUserProviderMock(): MockObject
    {
        assert($this->apiKeyUserProvider instanceof MockObject);

        return $this->apiKeyUserProvider;
    }
}
