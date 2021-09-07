<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Role/RoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Role;

use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class RoleControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/role';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/role` returns HTTP 401 for non-logged in user
     */
    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetBaseRouteReturn403
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/role` returns HTTP 403 when using `$username` + `$password` as a user
     */
    public function testThatGetBaseRouteReturn403(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetBaseRouteReturn200
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/role` returns HTTP 200 when using `$username` + `$password` as a user
     */
    public function testThatGetBaseRouteReturn200(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetBaseRouteReturn403(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetBaseRouteReturn200(): Generator
    {
        yield ['john-admin', 'password-admin'];
        yield ['john-root', 'password-root'];
    }
}
