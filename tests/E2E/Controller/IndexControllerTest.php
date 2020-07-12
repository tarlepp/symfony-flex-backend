<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/IndexControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller;

use App\Utils\Tests\WebTestCase;
use Throwable;

/**
 * Class IndexControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class IndexControllerTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatDefaultRouteReturns200(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/');

        $response = $client->getResponse();

        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
    }
}
