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
     * @testdox Test that `GET /v1/user/count` returns expected response with $username + $password
     */
    public function testThatCountActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
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
     * @testdox Test that `GET /v1/user/count` returns expected response with $role `ApiKey` token
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
     * @testdox Test that `GET /v1/user/count` returns HTTP 403 with $username + $password
     */
    public function testThatCountActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
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
     * @testdox Test that `GET /v1/user/count` returns HTTP 403 with $role `ApiKey` token
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
     * @testdox Test that `GET /v1/user` action returns expected with $username + $password
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
     * @testdox Test that `GET /v1/user` action returns HTTP 403 for invalid user $username + $password
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
     * @testdox Test that `GET /v1/user/ids` returns expected with $username + $password
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
     * @testdox Test that `GET /v1/user/ids` returns HTTP status 403 with invalid user $username + $password
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
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderInvalidApiKeyUsers(): Generator
    {
        yield ['api'];
        yield ['logged'];
        yield ['user'];
    }
}
