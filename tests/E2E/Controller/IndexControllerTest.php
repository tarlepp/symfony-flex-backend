<?php
declare(strict_types = 1);

/**
 * /tests/E2E/Controller/IndexControllerTest.php
 */

namespace App\Tests\E2E\Controller;

use App\Tests\E2E\TestCase\WebTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class IndexControllerTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /` request returns `200`')]
    public function testThatDefaultRouteReturns200(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/');

        $response = $client->getResponse();

        self::assertResponseStatusCode(Response::HTTP_OK, $response);
    }
}
