<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/RestTraitTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils\Tests;

use Exception;
use Generator;
use Ramsey\Uuid\Uuid;
use function array_merge;

/**
 * Class RestTraitTestCase
 *
 * @codeCoverageIgnore
 *
 * @package App\Utils\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestTraitTestCase extends WebTestCase
{
    /**
     * @var string;
     */
    private const END_POINT_COUNT = '/count';

    /**
     * @var string
     */
    protected static $route;

    /**
     * @return Generator
     */
    abstract public function getValidUsers(): Generator;

    /**
     * @return Generator
     */
    abstract public function getInvalidUsers(): Generator;

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatCountRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route . self::END_POINT_COUNT);

        $response = $client->getResponse();

        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatCountRouteWorksWithAllowedHttpMethods
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatCountRouteWorksWithAllowedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route . self::END_POINT_COUNT);

        $response = $client->getResponse();

        static::assertSame(500, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatCountRouteDoesNotAllowInvalidUser
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatCountRouteDoesNotAllowInvalidUser(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route . self::END_POINT_COUNT);

        $response = $client->getResponse();

        static::assertSame($username === null ? 401 : 403, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatRootRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route);

        $response = $client->getResponse();

        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatRootRouteWorksWithAllowedHttpMethods
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatRootRouteWorksWithAllowedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route);

        $response = $client->getResponse();

        static::assertSame(500, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatRootRouteDoesNotAllowInvalidUser
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatRootRouteDoesNotAllowInvalidUser(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route);

        $response = $client->getResponse();

        static::assertSame($username === null ? 401 : 403, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $uuid = Uuid::uuid4()->toString();

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route . '/' . $uuid);

        $response = $client->getResponse();

        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatRootRouteWithIdWorksWithAllowedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $uuid = Uuid::uuid4()->toString();

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route . '/' . $uuid);

        $response = $client->getResponse();

        static::assertSame(500, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdDoesNotAllowInvalidUser
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatRootRouteWithIdDoesNotAllowInvalidUser(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $uuid = Uuid::uuid4()->toString();

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route . '/' . $uuid);

        $response = $client->getResponse();

        static::assertSame($username === null ? 401 : 403, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatIdsRouteDoesNotAllowNotSupportedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route . '/ids');

        $response = $client->getResponse();

        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatIdsRouteWorksWithAllowedHttpMethods(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);
        $client->request($method, static::$route . '/ids');

        $response = $client->getResponse();

        static::assertSame(500, $response->getStatusCode(), $response->getContent());
    }

    /** @noinspection PhpUndefinedNamespaceInspection */
    /**
     * @dataProvider dataProviderTestThatIdsRouteDoesNotAllowInvalidUser
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $method
     *
     * @throws Exception
     */
    public function testThatIdsRouteDoesNotAllowInvalidUser(
        ?string $username = null,
        ?string $password = null,
        ?string $method = null
    ): void {
        $method = $method ?? 'GET';

        $client = $this->getTestClient($username, $password);

        $client->request($method, static::$route . '/ids');

        $response = $client->getResponse();

        static::assertSame($username === null ? 401 : 403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            ['POST'],
            ['HEAD'],
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatCountRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            ['GET'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatCountRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            ['GET'],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatRootRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            ['GET'],
            ['POST'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatRootRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            ['GET'],
            ['POST'],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            ['POST'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            ['DELETE'],
            ['GET'],
            ['PUT'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatRootRouteWithIdDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            ['DELETE'],
            ['GET'],
            ['PUT'],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods(): Generator
    {
        $methods = [
            ['POST'],
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods(): Generator
    {
        $methods = [
            ['GET'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatIdsRouteDoesNotAllowInvalidUser(): Generator
    {
        $methods = [
            ['GET'],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @param Generator $users
     * @param mixed[] $methods
     *
     * @return Generator
     */
    private function createDataForTest(Generator $users, array $methods): Generator
    {
        foreach ($users as $userData) {
            foreach ($methods as $method) {
                yield array_merge($userData, $method);
            }
        }
    }
}
