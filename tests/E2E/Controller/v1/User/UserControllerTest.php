<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

    /**
     * @throws Throwable
     *                  
     * @testdox Test that `GET /v1/user` returns HTTP status `401` for non-logged in user
     */
    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), (string)$response);

        self::assertJsonStringEqualsJsonString(
            '{"message":"JWT Token not found","code":401}',
            $content,
            "Response:\n" . $response,
        );
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/count` returns expected response when using `$u` + `$p` credentials
     */
    public function testThatCountActionReturnsExpected(string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString('{"count":6}', $content, "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderValidApiKeyUsers
     *
     * @testdox Test that `GET /v1/user/count` returns expected response when using API key token for `$role` role
     */
    public function testThatCountActionReturnsExpectedForApiKeyUser(string $role): void
    {
        $client = $this->getApiKeyClient($role);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString('{"count":6}', $content, "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/count` returns HTTP status `403` when using `$u` + `$p` credentials
     */
    public function testThatCountActionReturns403ForInvalidUser(string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $content,
            "Response:\n" . $response,
        );
    }

    /**
     * @dataProvider dataProviderInvalidApiKeyUsers
     *
     * @testdox Test that `GET /v1/user/count` returns HTTP status `403` when using API key token for `$role` role
     */
    public function testThatCountActionReturns403ForInvalidApiKeyUser(string $role): void
    {
        $client = $this->getApiKeyClient($role);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $content,
            "Response:\n" . $response,
        );
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user` action returns expected when using `$username` + `$password` credentials
     */
    public function testThatFindActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        self::assertCount(6, JSON::decode($content), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user` action returns HTTP status 403 when using `$username` + `$password` credentials
     */
    public function testThatFindActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $content,
            "Response:\n" . $response,
        );
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/ids` returns expected when using `$username` + `$password` credentials
     */
    public function testThatIdsActionReturnExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        self::assertCount(6, JSON::decode($content), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/ids` returns HTTP status `403`  when using `$username` + `$password` credentials
     */
    public function testThatIdsActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $content,
            "Response:\n" . $response,
        );
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderValidUsers(): Generator
    {
        yield ['john-admin', 'password-admin'];
        yield ['john-root', 'password-root'];
        yield ['john.doe-admin@test.com', 'password-admin'];
        yield ['john.doe-root@test.com', 'password-root'];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderValidApiKeyUsers(): Generator
    {
        yield ['admin'];
        yield ['root'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderInvalidUsers(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john.doe@test.com', 'password'];
        yield ['john.doe-api@test.com', 'password-api'];
        yield ['john.doe-logged@test.com', 'password-logged'];
        yield ['john.doe-user@test.com', 'password-user'];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderInvalidApiKeyUsers(): Generator
    {
        yield ['logged'];
        yield ['api'];
        yield ['user'];
    }
}
