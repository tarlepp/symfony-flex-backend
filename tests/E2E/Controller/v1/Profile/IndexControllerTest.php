<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Profile/IndexControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Profile;

use App\Security\Interfaces\RolesServiceInterface;
use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use JsonException;
use Throwable;
use function getenv;
use function str_pad;

/**
 * Class IndexControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class IndexControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/profile';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/profile` request returns `401` without Json Web Token
     */
    public function testThatProfileActionReturns401WithoutToken(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        self::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        self::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @dataProvider dataProviderTestThatProfileActionReturnExpectedWithValidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/profile` request returns `200` with valid user `$username` + `$password`
     */
    public function testThatProfileActionReturnExpectedWithValidUser(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @throws JsonException
     *
     * @testdox Test that `GET /v1/profile` request returns `401` with invalid API key token
     */
    public function testThatProfileActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        self::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        self::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @dataProvider dataProviderTestThatProfileActionReturnsExpectedWithValidApiKeyToken
     *
     * @throws JsonException
     *
     * @testdox Test that `GET /v1/profile` request returns `401` with valid `$token` API key token
     */
    public function testThatProfileActionReturnsExpectedWithValidApiKeyToken(string $token): void
    {
        $client = $this->getApiKeyClient($token);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        self::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        self::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @return Generator<array{0: string, 1:  string}>
     */
    public function dataProviderTestThatProfileActionReturnExpectedWithValidUser(): Generator
    {
        yield ['john', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
            yield ['john-admin', 'password-admin'];
            yield ['john-root', 'password-root'];
        }

        yield ['john.doe@test.com', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
            yield ['john.doe-admin@test.com', 'password-admin'];
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatProfileActionReturnsExpectedWithValidApiKeyToken(): Generator
    {
        $rolesService = self::getContainer()->get(RolesService::class);

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            foreach ($rolesService->getRoles() as $role) {
                yield [str_pad($rolesService->getShort($role), 40, '_')];
            }
        } else {
            yield [str_pad($rolesService->getShort(RolesServiceInterface::ROLE_LOGGED), 40, '_')];
        }
    }
}
