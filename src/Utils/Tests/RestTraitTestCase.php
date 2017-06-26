<?php
declare(strict_types=1);
/**
 * /src/Utils/Tests/WebTestCase.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils\Tests;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

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
     * @return array
     */
    abstract public function getValidUsers(): array;

    /**
     * @return array
     */
    abstract public function getInvalidUsers(): array;

    /**
     * @var string
     */
    protected static $route;

    /**
     * @dataProvider dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\CountAction::countAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatCountRouteDoesNotAllowNotSupportedHttpMethods(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatCountRouteWorksWithAllowedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\CountAction::countAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatCountRouteWorksWithAllowedHttpMethods(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(500, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatCountRouteDoesNotAllowInvalidUser
     *
     * @covers \App\Rest\Traits\Actions\Root\CountAction::countAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatCountRouteDoesNotAllowInvalidUser(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame($username === null ? 401 : 403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\CreateAction::createAction()
     * @covers \App\Rest\Traits\Actions\Root\FindAction::findAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatRootRouteDoesNotAllowNotSupportedHttpMethods(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWorksWithAllowedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\CreateAction::createAction()
     * @covers \App\Rest\Traits\Actions\Root\FindAction::findAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatRootRouteWorksWithAllowedHttpMethods(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(500, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteDoesNotAllowInvalidUser
     *
     * @covers \App\Rest\Traits\Actions\Root\CountAction::countAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatRootRouteDoesNotAllowInvalidUser(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame($username === null ? 401 : 403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\DeleteAction::deleteAction()
     * @covers \App\Rest\Traits\Actions\Root\FindOneAction::findOneAction()
     * @covers \App\Rest\Traits\Actions\Root\UpdateAction::updateAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $uuid = Uuid::uuid4()->toString();

        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/' . $uuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\DeleteAction::deleteAction()
     * @covers \App\Rest\Traits\Actions\Root\FindOneAction::findOneAction()
     * @covers \App\Rest\Traits\Actions\Root\UpdateAction::updateAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatRootRouteWithIdWorksWithAllowedHttpMethods(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $uuid = Uuid::uuid4()->toString();

        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/' . $uuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(500, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdDoesNotAllowInvalidUser
     *
     * @covers \App\Rest\Traits\Actions\Root\DeleteAction::deleteAction()
     * @covers \App\Rest\Traits\Actions\Root\FindOneAction::findOneAction()
     * @covers \App\Rest\Traits\Actions\Root\UpdateAction::updateAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatRootRouteWithIdDoesNotAllowInvalidUser(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $uuid = Uuid::uuid4()->toString();

        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/' . $uuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame($username === null ? 401 : 403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\IdsAction::idsAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatIdsRouteDoesNotAllowNotSupportedHttpMethods(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\IdsAction::idsAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatIdsRouteWorksWithAllowedHttpMethods(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(500, $response->getStatusCode(), $response->getContent());
    }
    
    /**
     * @dataProvider dataProviderTestThatIdsRouteDoesNotAllowInvalidUser
     *
     * @covers \App\Rest\Traits\Actions\Root\IdsAction::idsAction()
     *
     * @param string|null $username
     * @param string|null $password
     * @param string      $method
     */
    public function testThatIdsRouteDoesNotAllowInvalidUser(
        string $username = null,
        string $password = null,
        string $method
    ): void
    {
        $client = $this->getClient($username, $password);
        $client->request($method, static::$route . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame($username === null ? 401 : 403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods(): array
    {
        $methods = [
            ['HEAD'],
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
     * @return array
     */
    public function dataProviderTestThatCountRouteWorksWithAllowedHttpMethods(): array
    {
        $methods = [
            ['GET'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatCountRouteDoesNotAllowInvalidUser(): array
    {
        $methods = [
            ['GET'],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods(): array
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
     * @return array
     */
    public function dataProviderTestThatRootRouteWorksWithAllowedHttpMethods(): array
    {
        $methods = [
            ['GET'],
            ['POST'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRootRouteDoesNotAllowInvalidUser(): array
    {
        $methods = [
            ['GET'],
            ['POST'],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(): array
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
     * @return array
     */
    public function dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods(): array
    {
        $methods = [
            ['DELETE'],
            ['GET'],
            ['PUT'],
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRootRouteWithIdDoesNotAllowInvalidUser(): array
    {
        $methods = [
            ['DELETE'],
            ['GET'],
            ['PUT'],
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods(): array
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
     * @return array
     */
    public function dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods(): array
    {
        $methods = [
            ['GET']
        ];

        return $this->createDataForTest($this->getValidUsers(), $methods);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatIdsRouteDoesNotAllowInvalidUser(): array
    {
        $methods = [
            ['GET']
        ];

        return $this->createDataForTest($this->getInvalidUsers(), $methods);
    }

    /**
     * @param array $users
     * @param array $methods
     *
     * @return array
     */
    private function createDataForTest(array $users, array $methods): array
    {
        $output = [];

        foreach ($users as $userData) {
            foreach ($methods as $method) {
                $output[] = \array_merge($userData, $method);
            }
        }

        return $output;
    }
}