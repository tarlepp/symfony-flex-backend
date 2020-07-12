<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/VersionControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller;

use App\Resource\LogRequestResource;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Throwable;
use function file_get_contents;

/**
 * Class VersionControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class VersionControllerTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatVersionRouteReturns200(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/version');

        $response = $client->getResponse();

        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
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

        $client = $this->getTestClient();
        $client->request('GET', '/version');

        static::assertSame($expectedLogCount, $resource->count());
    }

    /**
     * @throws Throwable
     */
    public function testThatApiVersionIsAddedToResponseHeaders(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/version');

        $response = $client->getResponse();

        $version = $response->headers->get('X-API-VERSION');

        static::assertNotNull($version);
        static::assertSame(JSON::decode(file_get_contents(__DIR__ . '/../../../composer.json'))->version, $version);
    }
}
