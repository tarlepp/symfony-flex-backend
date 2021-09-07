<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/UserGroup/v1/UserGroupControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\UserGroup;

use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserGroupControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user_group';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user_group` returns HTTP 401 for non-logged in user
     */
    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetBaseRouteReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user_group` returns HTTP 403 when using $username + $password as a user
     */
    public function testThatGetBaseRouteReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $content,
            "Response:\n" . $response
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetBaseRouteReturns200ForValidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user_group` returns HTTP 200 when using $username + $password as a user
     */
    public function testThatGetBaseRouteReturns200ForValidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetBaseRouteReturns403ForInvalidUser(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetBaseRouteReturns200ForValidUser(): Generator
    {
        yield ['john-admin', 'password-admin'];
        yield ['john-root', 'password-root'];
    }
}
