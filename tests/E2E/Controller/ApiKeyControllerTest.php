<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/ApiKeyControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Controller;

use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class ApiKeyControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyControllerTest extends WebTestCase
{
    private string $baseUrl = '/api_key';

    /**
     * @throws Throwable
     */
    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatFindActionWorksAsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that find action returns $expectedStatus with $username + $password
     */
    public function testThatFindActionWorksAsExpected(string $username, string $password, int $expectedStatus): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame($expectedStatus, $response->getStatusCode(), "Response:\n" . $response);
    }

    public function dataProviderTestThatFindActionWorksAsExpected(): Generator
    {
        //yield ['john', 'password', 403];
        //yield ['john-api', 'password-api', 403];
        //yield ['john-logged', 'password-logged', 403];
        //yield ['john-user', 'password-user', 403];
        yield ['john-admin', 'password-admin', 403];
        yield ['john-root', 'password-root', 200];
    }
}
