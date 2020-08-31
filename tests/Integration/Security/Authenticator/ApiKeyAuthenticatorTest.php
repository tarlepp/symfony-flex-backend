<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Authenticator/ApiKeyAuthenticatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Security\Authenticator;

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
use function json_encode;

/**
 * Class ApiKeyAuthenticatorTest
 *
 * @package App\Tests\Integration\Security\Authenticator
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyAuthenticatorTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatSupportReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `supports` method returns `$expected` for request `$request`
     */
    public function testThatSupportReturnsExpected(bool $expected, Request $request): void
    {
        /**
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         */
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new ApiKeyAuthenticator($apiKeyUserProvider);

        static::assertSame($expected, $authenticator->supports($request));
    }

    /**
     * @throws Throwable
     */
    public function testThatStartMethodReturnsExpected(): void
    {
        /**
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         */
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new ApiKeyAuthenticator($apiKeyUserProvider);

        $output = $authenticator->start(new Request());

        static::assertSame(401, $output->getStatusCode());
        static::assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Authentication Required'], JSON_THROW_ON_ERROR),
            $output->getContent()
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetCredentialsReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `getCredentials` method returns `$expected` with `$request` request.
     */
    public function testThatGetCredentialsReturnsExpected(?StringableArrayObject $expected, Request $request): void
    {
        /**
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         */
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new ApiKeyAuthenticator($apiKeyUserProvider);

        static::assertSame(
            $expected === null ? null : $expected->getArrayCopy(),
            $authenticator->getCredentials($request)
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetUserReturnsExpected
     *
     * @param mixed $credentials
     *
     * @throws Throwable
     *
     * @testdox Test that `getUser` returns null with `$credentials` credentials.
     */
    public function testThatGetUserReturnsExpectedWhenCredentialsIsInvalid($credentials): void
    {
        /**
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         */
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new ApiKeyAuthenticator($apiKeyUserProvider);

        static::assertNull(
            $authenticator->getUser(
                $credentials instanceof StringableArrayObject ? $credentials->getArrayCopy() : $credentials,
                $apiKeyUserProvider
            )
        );
    }

    /**
     * @dataProvider dataProviderTestThatCheckCredentialsThrowsAnException
     *
     * @param mixed $credentials
     *
     * @throws Throwable
     *
     * @testdox Test that `checkCredentials` method throws `Invalid token` exception with `$credentials` credentials.
     */
    public function testThatCheckCredentialsThrowsAnException($credentials): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token');

        /**
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         */
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new ApiKeyAuthenticator($apiKeyUserProvider))->checkCredentials(
            $credentials instanceof StringableArrayObject ? $credentials->getArrayCopy() : $credentials,
            new User('user', 'password')
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationSuccessReturnsNull(): void
    {
        /**
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         */
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new ApiKeyAuthenticator($apiKeyUserProvider);

        static::assertNull($authenticator->onAuthenticationSuccess(
            new Request(),
            new AnonymousToken('secret', 'user'),
            'foobar'
        ));
    }

    /**
     * @throws Throwable
     */
    public function testThatOnAuthenticationFailureReturnsExpected(): void
    {
        /**
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         */
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $output = (new ApiKeyAuthenticator($apiKeyUserProvider))
            ->onAuthenticationFailure(new Request(), new AuthenticationException('foobar'));

        static::assertSame(Response::HTTP_FORBIDDEN, $output->getStatusCode());
        static::assertJsonStringEqualsJsonString(
            json_encode(['message' => 'An authentication exception occurred.'], JSON_THROW_ON_ERROR),
            $output->getContent()
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatSupportsRememberMeReturnsFalse(): void
    {
        /**
         * @var MockObject|ApiKeyUserProvider $apiKeyUserProvider
         */
        $apiKeyUserProvider = $this->getMockBuilder(ApiKeyUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        static::assertFalse((new ApiKeyAuthenticator($apiKeyUserProvider))->supportsRememberMe());
    }

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
}
