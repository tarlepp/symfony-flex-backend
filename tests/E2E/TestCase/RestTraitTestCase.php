<?php
declare(strict_types = 1);
/**
 * /tests/E2E/TestCase/RestTraitTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\TestCase;

use App\Tests\Utils\PhpUnitUtil;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_merge;

/**
 * @codeCoverageIgnore
 *
 * @package App\Tests\E2E\TestCase
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class RestTraitTestCase extends WebTestCase
{
    private const string END_POINT_COUNT = '/count';
    private const string INVALID_METHOD = 'foobar';

    protected static string $route;

    /**
     * @throws Throwable
     */
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        PhpUnitUtil::loadFixtures($kernel);

        $kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    abstract public static function getValidUsers(): Generator;

    /**
     * @return Generator<array<int, string|null>>
     */
    abstract public static function getInvalidUsers(): Generator;

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods')]
    #[TestDox('Test that `$m /count` request returns `405` when using invalid user `$u` + `$p`')]
    public function testThatCountRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . self::END_POINT_COUNT, $u, $p, $m);

        static::assertSame(405, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatCountRouteWorksWithAllowedHttpMethods')]
    #[TestDox('Test that `$m /count` request returns `200` when using valid user `$u` + `$p`')]
    public function testThatCountRouteWorksWithAllowedHttpMethods(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . self::END_POINT_COUNT, $u, $p, $m);

        static::assertSame(200, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatCountRouteDoesNotAllowInvalidUser')]
    #[TestDox('Test that `$m /count` request returns `401` or `403` when using invalid user `$u` + `$p`')]
    public function testThatCountRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . self::END_POINT_COUNT, $u, $p, $m);

        static::assertSame(
            $u === null ? 401 : 403,
            $response->getStatusCode(),
            (string)$response->getContent()
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods')]
    #[TestDox('Test that `$m /` request returns `405` when using valid user `$u` + `$p`')]
    public function testThatRootRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route, $u, $p, $m);

        static::assertSame(405, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatRootRouteWorksWithAllowedHttpMethods')]
    #[TestDox('Test that `$m /` request returns `200` or `400` when using valid user `$u` + `$p`')]
    public function testThatRootRouteWorksWithAllowedHttpMethods(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route, $u, $p, $m);

        $m === Request::METHOD_GET
            ? static::assertSame(200, $response->getStatusCode(), (string)$response->getContent())
            : static::assertSame(400, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatRootRouteDoesNotAllowInvalidUser')]
    #[TestDox('Test that `$m /` request returns `401` or `403` when using invalid user `$u` + `$p`')]
    public function testThatRootRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route, $u, $p, $m);

        static::assertSame(
            $u === null ? 401 : 403,
            $response->getStatusCode(),
            (string)$response->getContent()
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods')]
    #[TestDox('Test that `$m /$uuid` request returns `405` when using valid user `$u` + `$p`')]
    public function testThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(
        string $uuid,
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . '/' . $uuid, $u, $p, $m);

        static::assertSame(405, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods')]
    #[TestDox('Test that `$m /$uuid` request returns `200` or `400` when using valid user `$u` + `$p`')]
    public function testThatRootRouteWithIdWorksWithAllowedHttpMethods(
        string $uuid,
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . '/' . $uuid, $u, $p, $m);

        $m === Request::METHOD_PUT
            ? static::assertSame(400, $response->getStatusCode(), (string)$response->getContent())
            : static::assertSame(200, $response->getStatusCode(), (string)$response->getContent());

        if ($m === Request::METHOD_DELETE) {
            self::tearDownAfterClass();
        }
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatRootRouteWithIdDoesNotAllowInvalidUser')]
    #[TestDox('Test that `$m /$uuid` request returns `401` or `403` when using invalid user `$u` + `$p`')]
    public function testThatUuidRouteWithIdDoesNotAllowInvalidUser(
        string $uuid,
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . '/' . $uuid, $u, $p, $m);

        static::assertSame(
            $u === null ? 401 : 403,
            $response->getStatusCode(),
            (string)$response->getContent()
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods')]
    #[TestDox('Test that `$m /ids` request returns `405` when using valid user `$u` + `$p`')]
    public function testThatIdsRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . '/ids', $u, $p, $m);

        static::assertSame(405, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods')]
    #[TestDox('Test that `$m /ids` request returns `200` when using valid user `$u` + `$p`')]
    public function testThatIdsRouteWorksWithAllowedHttpMethods(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . '/ids', $u, $p, $m);

        static::assertSame(200, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatIdsRouteDoesNotAllowInvalidUser')]
    #[TestDox('Test that `$m /ids` request returns `401` or `403` when using invalid user `$u` + `$p`')]
    public function testThatIdsRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null,
    ): void {
        $response = $this->getClientResponse(static::$route . '/ids', $u, $p, $m);

        static::assertSame(
            $u === null ? 401 : 403,
            $response->getStatusCode(),
            (string)$response->getContent()
        );
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods(): Generator
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

        return self::createDataForTest(static::getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatCountRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
        ];

        return self::createDataForTest(static::getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatCountRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
        ];

        return self::createDataForTest(static::getInvalidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_PUT],
            [Request::METHOD_DELETE],
            [Request::METHOD_OPTIONS],
            [Request::METHOD_CONNECT],
            [self::INVALID_METHOD],
        ];

        return self::createDataForTest(static::getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatRootRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
            [Request::METHOD_POST],
        ];

        return self::createDataForTest(static::getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatRootRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
            [Request::METHOD_POST],
        ];

        return self::createDataForTest(static::getInvalidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_POST],
            [Request::METHOD_OPTIONS],
            [Request::METHOD_CONNECT],
            [self::INVALID_METHOD],
        ];

        return self::createDataForTest(static::getValidUsers(), $methods, true);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_DELETE],
            [Request::METHOD_GET],
            [Request::METHOD_PUT],
            [Request::METHOD_PATCH],
        ];

        return self::createDataForTest(static::getValidUsers(), $methods, true);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatRootRouteWithIdDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            [Request::METHOD_DELETE],
            [Request::METHOD_GET],
            [Request::METHOD_PUT],
            [Request::METHOD_PATCH],
        ];

        return self::createDataForTest(static::getInvalidUsers(), $methods, true);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_POST],
            [Request::METHOD_PUT],
            [Request::METHOD_DELETE],
            [Request::METHOD_OPTIONS],
            [Request::METHOD_CONNECT],
            [self::INVALID_METHOD],
        ];

        return self::createDataForTest(static::getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
        ];

        return self::createDataForTest(static::getValidUsers(), $methods);
    }

    /**
     * @return Generator<array<int, string|null>>
     */
    public static function dataProviderTestThatIdsRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            [Request::METHOD_GET],
        ];

        return self::createDataForTest(static::getInvalidUsers(), $methods);
    }

    /**
     * @param Generator<array<int, string|null>> $users
     * @param array<int, array<int, string>> $methods
     *
     * @return Generator<array<int, string|null>>
     */
    private static function createDataForTest(Generator $users, array $methods, ?bool $uuid = null): Generator
    {
        $uuid ??= false;

        foreach ($users as $userData) {
            foreach ($methods as $method) {
                yield array_merge(
                    $uuid ? ['20000000-0000-1000-8000-000000000001'] : [],
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
