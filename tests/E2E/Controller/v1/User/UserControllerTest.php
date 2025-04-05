<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\Tests\E2E\TestCase\WebTestCase;
use App\Utils\JSON;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function getenv;

/**
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/user` request returns `401` for non-logged in user')]
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
     * @throws Throwable
     */
    #[DataProvider('dataProviderValidUsers')]
    #[TestDox('Test that `GET /v1/user/count` request returns expected response when using valid user `$u` + `$p`')]
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

    #[DataProvider('dataProviderValidApiKeyUsers')]
    #[TestDox(
        'Test that `GET /v1/user/count` request returns expected response when using API key token for `$r` role'
    )]
    public function testThatCountActionReturnsExpectedForApiKeyUser(string $r): void
    {
        $client = $this->getApiKeyClient($r);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString('{"count":6}', $content, "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderInvalidUsers')]
    #[TestDox('Test that `GET /v1/user/count` request returns `403` when using invalid user `$u` + `$p`')]
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

    #[DataProvider('dataProviderInvalidApiKeyUsers')]
    #[TestDox('Test that `GET /v1/user/count` request returns `403` when using API key token for `$role` role')]
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
     * @throws Throwable
     */
    #[DataProvider('dataProviderValidUsers')]
    #[TestDox('Test that `GET /v1/user` request returns expected when using valid user `$username` + `$password`')]
    public function testThatFindActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertJson($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

        $json = JSON::decode($content);

        self::assertIsArray($json);
        self::assertCount(6, $json, "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderInvalidUsers')]
    #[TestDox('Test that `GET /v1/user` request returns 403 when using invalid user `$username` + `$password`')]
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
     * @throws Throwable
     */
    #[DataProvider('dataProviderValidUsers')]
    #[TestDox('Test that `GET /v1/user/ids` request returns expected when using valid user `$username` + `$password`')]
    public function testThatIdsActionReturnExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertJson($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

        $json = JSON::decode($content);

        self::assertIsArray($json);
        self::assertCount(6, $json, "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderInvalidUsers')]
    #[TestDox('Test that `GET /v1/user/ids` request returns `403` when using invalid user `$username` + `$password`')]
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
    public static function dataProviderValidUsers(): Generator
    {
        yield ['john-admin', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-root', 'password-root'];
        }

        yield ['john.doe-admin@test.com', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderValidApiKeyUsers(): Generator
    {
        yield ['admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['root'];
        }
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public static function dataProviderInvalidUsers(): Generator
    {
        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john', 'password'];
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
        }

        yield ['john-user', 'password-user'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password'];
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
        }

        yield ['john.doe-user@test.com', 'password-user'];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderInvalidApiKeyUsers(): Generator
    {
        yield ['logged'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['api'];
            yield ['user'];
        }
    }
}
