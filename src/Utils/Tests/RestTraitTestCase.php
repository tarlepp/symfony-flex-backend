<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/RestTraitTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils\Tests;

use App\Rest\UuidHelper;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_merge;

/**
 * Class RestTraitTestCase
 *
 * @codeCoverageIgnore
 *
 * @package App\Utils\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestTraitTestCase extends WebTestCase
{
    private const END_POINT_COUNT = '/count';
    private const INVALID_METHOD = 'foobar';

    protected static string $route;

    /**
     * @return Generator<array<int, string|null>>
     */
    abstract public function getValidUsers(): Generator;

    /**
     * @return Generator<array<int, string|null>>
     */
    abstract public function getInvalidUsers(): Generator;

    /**
     * @dataProvider dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `/count` route doesn't allow `$method` method with `$username + $password`.
     */
    public function testThatCountRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . self::END_POINT_COUNT, $username, $password, $method);

        static::assertSame(405, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatCountRouteWorksWithAllowedHttpMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `/count` route works as expected when using `$method` method with `$username + $password`.
     */
    public function testThatCountRouteWorksWithAllowedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . self::END_POINT_COUNT, $username, $password, $method);

        static::assertSame(500, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatCountRouteDoesNotAllowInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `/count` route doesn't allow invalid user `$username + $password` when using `$method` method.
     */
    public function testThatCountRouteDoesNotAllowInvalidUser(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . self::END_POINT_COUNT, $username, $password, $method);

        static::assertSame(
            $username === null ? 401 : 403,
            $response->getStatusCode(),
            (string)$response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `/` route doesn't allow `$method` method with `$username + $password`.
     */
    public function testThatRootRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route, $username, $password, $method);

        static::assertSame(405, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWorksWithAllowedHttpMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `/` route works as expected when using `$method` method with `$username + $password`.
     */
    public function testThatRootRouteWorksWithAllowedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route, $username, $password, $method);

        static::assertSame(500, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteDoesNotAllowInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `/` route doesn't allow invalid user `$username + $password` when using `$method` method.
     */
    public function testThatRootRouteDoesNotAllowInvalidUser(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route, $username, $password, $method);

        static::assertSame(
            $username === null ? 401 : 403,
            $response->getStatusCode(),
            (string)$response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `/$uuid` route doesn't allow `$method` method with `$username + $password`.
     */
    public function testThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(
        string $uuid,
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . '/' . $uuid, $username, $password, $method);

        static::assertSame(405, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `/$uuid` route works as expected when using `$method` method with `$username + $password`.
     */
    public function testThatRootRouteWithIdWorksWithAllowedHttpMethods(
        string $uuid,
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . '/' . $uuid, $username, $password, $method);

        static::assertSame(500, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdDoesNotAllowInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `/$uuid` route doesn't allow invalid user `$username + $password` when using `$method` method.
     */
    public function testThatRootRouteWithIdDoesNotAllowInvalidUser(
        string $uuid,
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . '/' . $uuid, $username, $password, $method);

        static::assertSame(
            $username === null ? 401 : 403,
            $response->getStatusCode(),
            (string)$response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `/ids` route doesn't allow `$method` method with `$username + $password`.
     */
    public function testThatIdsRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . '/ids', $username, $password, $method);

        static::assertSame(405, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods
     *
     * @throws Throwable
     *
     * @testdox Test that `/ids` route works as expected when using `$method` method with `$username + $password`.
     */
    public function testThatIdsRouteWorksWithAllowedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . '/ids', $username, $password, $method);

        static::assertSame(500, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatIdsRouteDoesNotAllowInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `/ids` route doesn't allow invalid user `$username + $password` when using `$method` method.
     */
    public function testThatIdsRouteDoesNotAllowInvalidUser(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $response = $this->getClientResponse(static::$route . '/ids', $username, $password, $method);

        static::assertSame(
            $username === null ? 401 : 403,
            $response->getStatusCode(),
            (string)$response->getContent()
        );
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_POST],
            [Request::METHOD_HEAD],
            [Request::METHOD_PUT],
            [Request::METHOD_DELETE],
            [Request::METHOD_OPTIONS],
            [Request::METHOD_CONNECT],
            [self::INVALID_METHOD],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatCountRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatCountRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_PUT],
            [Request::METHOD_DELETE],
            [Request::METHOD_OPTIONS],
            [Request::METHOD_CONNECT],
            [self::INVALID_METHOD],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatRootRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
            [Request::METHOD_POST],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatRootRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
            [Request::METHOD_POST],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_POST],
            [Request::METHOD_OPTIONS],
            [Request::METHOD_CONNECT],
            [self::INVALID_METHOD],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods, true);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_DELETE],
            [Request::METHOD_GET],
            [Request::METHOD_PUT],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods, true);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatRootRouteWithIdDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            [Request::METHOD_DELETE],
            [Request::METHOD_GET],
            [Request::METHOD_PUT],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods, true);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_POST],
            [Request::METHOD_PUT],
            [Request::METHOD_DELETE],
            [Request::METHOD_OPTIONS],
            [Request::METHOD_CONNECT],
            [self::INVALID_METHOD],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public function dataProviderTestThatIdsRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @param Generator<array<int, string|null>> $users
     * @param array<int, array<int, string>> $methods
     *
     * @return Generator<array<int, string|null>>
     */
    private function createDataForTest(Generator $users, array $methods, ?bool $uuid = null): Generator
    {
        $uuid ??= false;

        foreach ($users as $userData) {
            foreach ($methods as $method) {
                yield array_merge(
                    $uuid ? [UuidHelper::getFactory()->uuid1()->toString()] : [],
                    $userData,
                    $method
                );
            }
        }
    }

    /**
     * @throws Throwable
     */
    private function getClientResponse(
        string $route,
        ?string $username,
        ?string $password,
        ?string $method
    ): Response {
        $method ??= Request::METHOD_GET;

        $client = $this->getTestClient($username, $password);
        $client->request($method, $route);

        return $client->getResponse();
    }
}
