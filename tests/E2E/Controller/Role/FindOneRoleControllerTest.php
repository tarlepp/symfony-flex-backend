<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Role/FindOneRoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\Role;

use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class FindOneRoleControllerTest
 *
 * @package App\Tests\E2E\Controller\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindOneRoleControllerTest extends WebTestCase
{
    private string $baseUrl = '/role';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /role/ROLE_ADMIN` returns HTTP 401 for non-logged in user.
     */
    public function testThatFindOneRoleReturns401(): void
    {
        $client = $this->getTestClient();

        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatFindOneRoleReturns403
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /role/ROLE_ADMIN` returns HTTP 403 when using `$username` + `$password` as a user.
     */
    public function testThatFindOneRoleReturns403(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatFindOneActionWorksAsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `findOne` action returns HTTP 200 with $username + $password
     */
    public function testThatFindOneActionWorksAsExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);

        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    public function dataProviderTestThatFindOneRoleReturns403(): Generator
    {
        //yield ['john', 'password'];
        //yield ['john-api', 'password-api'];
        //yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
    }

    public function dataProviderTestThatFindOneActionWorksAsExpected(): Generator
    {
        yield ['john-admin', 'password-admin'];
        //yield ['john-root',   'password-root'];
    }
}
