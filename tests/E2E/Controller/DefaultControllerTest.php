<?php
declare(strict_types=1);
/**
 * /tests/E2E/Controller/DefaultControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Controller;

use App\Resource\LogRequestResource;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Throwable;
use function file_get_contents;

/**
 * Class DefaultControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DefaultControllerTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatDefaultRouteReturns200(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/');

        $response = $client->getResponse();

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

        unset($response, $client);
    }

    /**
     * @throws Throwable
     */
    public function testThatHealthzRouteReturns200(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/healthz');

        $response = $client->getResponse();

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

        unset($response, $client);
    }

    /**
     * @throws Throwable
     */
    public function testThatHealthzRouteDoesNotMakeRequestLog(): void
    {
        static::bootKernel();

        /** @var LogRequestResource $resource */
        $resource = static::$container->get(LogRequestResource::class);

        $expectedLogCount = $resource->count();

        $client = $this->getClient();
        $client->request('GET', '/healthz');

        static::assertSame($expectedLogCount, $resource->count());

        unset($client, $resource);
    }

    /**
     * @throws Throwable
     */
    public function testThatVersionRouteReturns200(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/version');

        $response = $client->getResponse();

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

        unset($response, $client);
    }

    /**
     * @throws Throwable
     */
    public function testThatVersionRouteDoesNotMakeRequestLog(): void
    {
        static::bootKernel();

        /** @var LogRequestResource $resource */
        $resource = static::$container->get(LogRequestResource::class);

        $expectedLogCount = $resource->count();

        $client = $this->getClient();
        $client->request('GET', '/version');

        static::assertSame($expectedLogCount, $resource->count());

        unset($client, $resource);
    }

    /**
     * @throws Throwable
     */
    public function testThatApiVersionIsAddedToResponseHeaders(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/version');

        $response = $client->getResponse();

        /** @noinspection NullPointerExceptionInspection */
        $version = $response->headers->get('X-API-VERSION');

        static::assertNotNull($version);
        static::assertSame(JSON::decode(file_get_contents(__DIR__ . '/../../../composer.json'))->version, $version);

        unset($response, $client);
    }
}
