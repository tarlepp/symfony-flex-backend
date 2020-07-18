<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/User/UserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\User;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class UserControllerTest
 *
 * @package App\Tests\E2E\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserControllerTest extends WebTestCase
{
    private string $baseUrl = '/user';

    /**
     * @throws Throwable
     */
    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), (string)$response);

        static::assertJsonStringEqualsJsonString(
            '{"message":"JWT Token not found","code":401}',
            $response->getContent(),
            "Response:\n" . $response
        );
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /count` returns expected response with $username + $password
     */
    public function testThatCountActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString('{"count":6}', $response->getContent(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderValidApiKeyUsers
     *
     * @testdox Test that `GET /count` returns expected response with $role `ApiKey` token
     */
    public function testThatCountActionReturnsExpectedForApiKeyUser(string $role): void
    {
        $client = $this->getApiKeyClient($role);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString('{"count":6}', $response->getContent(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /count` returns HTTP 403 with $username + $password
     */
    public function testThatCountActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/count');

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
     * @dataProvider dataProviderInvalidApiKeyUsers
     *
     * @testdox Test that `GET /count` returns HTTP 403 with $role `ApiKey` token
     */
    public function testThatCountActionReturns403ForInvalidApiKeyUser(string $role): void
    {
        $client = $this->getApiKeyClient($role);
        $client->request('GET', $this->baseUrl . '/count');

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
     * @dataProvider dataProviderValidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `Find` action returns expected with $username + $password
     */
    public function testThatFindActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        static::assertCount(6, JSON::decode($response->getContent()), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `find` action returns HTTP 403 for invalid user $username + $password
     */
    public function testThatFindActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent(),
            "Response:\n" . $response
        );
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /user/ids` returns expected with $username + $password
     */
    public function testThatIdsActionReturnExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        static::assertCount(6, JSON::decode($response->getContent()), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /user/ids` returns HTTP status 403 with invalid user $username + $password
     */
    public function testThatIdsActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent(),
            "Response:\n" . $response
        );
    }

    public function dataProviderValidUsers(): Generator
    {
        yield ['john-admin', 'password-admin'];
        //yield ['john-root', 'password-root'];
    }

    public function dataProviderValidApiKeyUsers(): Generator
    {
        yield ['admin'];
        //yield ['root'];
    }

    public function dataProviderInvalidUsers(): Generator
    {
        //yield ['john', 'password'];
        //yield ['john-api', 'password-api'];
        //yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
    }

    public function dataProviderInvalidApiKeyUsers(): Generator
    {
        //yield ['api'];
        //yield ['logged'];
        yield ['user'];
    }
}
