<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/UserGroup/UserGroupControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\UserGroup;

use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class UserGroupControllerTest
 *
 * @package App\Tests\E2E\Controller\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupControllerTest extends WebTestCase
{
    private string $baseUrl = '/user_group';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /user_group` returns HTTP 401 for non-logged in user
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
     * @dataProvider dataProviderTestThatGetBaseRouteReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /user_group` returns HTTP 403 when using $username + $password as a user
     */
    public function testThatGetBaseRouteReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent(),
            "Response:\n" . $response
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetBaseRouteReturns200ForValidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /user_group` returns HTTP 200 when using $username + $password as a user
     */
    public function testThatGetBaseRouteReturns200ForValidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
    }

    public function dataProviderTestThatGetBaseRouteReturns403ForInvalidUser(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
    }

    public function dataProviderTestThatGetBaseRouteReturns200ForValidUser(): Generator
    {
        yield ['john-admin', 'password-admin'];
        yield ['john-root', 'password-root'];
    }
}
