<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Authenticator/ApiKeyAuthenticatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Throwable;
use function assert;

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
}
