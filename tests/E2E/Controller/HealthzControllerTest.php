<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/HealthzControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller;

use App\Resource\LogRequestResource;
use App\Utils\Tests\WebTestCase;
use Throwable;

/**
 * Class IndexControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class HealthzControllerTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatHealthzRouteReturns200(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/healthz');

        $response = $client->getResponse();

        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
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

        $client = $this->getTestClient();
        $client->request('GET', '/healthz');

        static::assertSame($expectedLogCount, $resource->count());
    }
}
