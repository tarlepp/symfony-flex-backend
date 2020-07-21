<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Role/RoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\Role;

use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class RoleControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleControllerTest extends WebTestCase
{
    private string $baseUrl = '/role';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /role` returns HTTP 401 for non-logged in user
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
     * @dataProvider dataProviderTestThatGetBaseRouteReturn403
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /role` returns HTTP 403 when using `$username` + `$password` as a user
     */
    public function testThatGetBaseRouteReturn403(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetBaseRouteReturn200
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /role` returns HTTP 200 when using `$username` + `$password` as a user.
     */
    public function testThatGetBaseRouteReturn200(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
    }

    public function dataProviderTestThatGetBaseRouteReturn403(): Generator
    {
        //yield ['john', 'password'];
        //yield ['john-api', 'password-api'];
        //yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
    }

    public function dataProviderTestThatGetBaseRouteReturn200(): Generator
    {
        yield ['john-admin', 'password-admin'];
        //yield ['john-root', 'password-root'];
    }
}
