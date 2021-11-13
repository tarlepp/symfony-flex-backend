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
     *
     * @testdox Test that `GET /version` request returns `200`
     */
    public function testThatVersionRouteReturns200(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/version');

        $response = $client->getResponse();

        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /version` request doesn't add request log row to database
     */
    public function testThatVersionRouteDoesNotMakeRequestLog(): void
    {
        $resource = self::getContainer()->get(LogRequestResource::class);

        $expectedLogCount = $resource->count();

        $client = $this->getTestClient();
        $client->request('GET', '/version');

        self::assertSame($expectedLogCount, $resource->count());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `X-API-VERSION` header is added to response with expected value
     */
    public function testThatApiVersionIsAddedToResponseHeaders(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/version');

        $response = $client->getResponse();

        $version = $response->headers->get('X-API-VERSION');

        self::assertNotNull($version);
        self::assertSame(
            JSON::decode((string)file_get_contents(__DIR__ . '/../../../composer.json'))->version,
            $version,
        );
    }
}
