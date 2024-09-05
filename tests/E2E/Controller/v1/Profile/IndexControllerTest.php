<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Profile/IndexControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Profile;

use App\Enum\Role;
use App\Security\RolesService;
use App\Tests\E2E\TestCase\WebTestCase;
use App\Utils\JSON;
use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function getenv;
use function property_exists;
use function str_pad;

/**
 * @package App\Tests\E2E\Controller\v1\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class IndexControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/profile';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/profile` request returns `401` without Json Web Token')]
    public function testThatProfileActionReturns401WithoutToken(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        self::assertIsObject($responseContent);

        $info = "\nResponse:\n" . $response;

        self::assertTrue(property_exists($responseContent, 'code'), 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertTrue(property_exists($responseContent, 'message'), 'Response does not contain "message"' . $info);
        self::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatProfileActionReturnExpectedWithValidUser')]
    #[TestDox('Test that `GET /v1/profile` request returns `200` with valid user `$username` + `$password`')]
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
     */
    #[TestDox('Test that `GET /v1/profile` request returns `401` with invalid API key token')]
    public function testThatProfileActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        self::assertIsObject($responseContent);

        $info = "\nResponse:\n" . $response;

        self::assertTrue(property_exists($responseContent, 'code'), 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertTrue(property_exists($responseContent, 'message'), 'Response does not contain "message"' . $info);
        self::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @throws JsonException
     */
    #[DataProvider('dataProviderTestThatProfileActionReturnsExpectedWithValidApiKeyToken')]
    #[TestDox('Test that `GET /v1/profile` request returns `401` with valid `$token` API key token ($role - role)')]
    public function testThatProfileActionReturnsExpectedWithValidApiKeyToken(string $token, string $role): void
    {

        $client = $this->getApiKeyClient($role);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        self::assertIsObject($responseContent);

        $info = "\nResponse:\n" . $response;

        self::assertTrue(property_exists($responseContent, 'code'), 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertTrue(property_exists($responseContent, 'message'), 'Response does not contain "message"' . $info);
        self::assertSame(
            'Invalid API key',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @return Generator<array-key, array{0: string, 1:  string}>
     */
    public static function dataProviderTestThatProfileActionReturnExpectedWithValidUser(): Generator
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
     * @return Generator<array-key, array{0: string}>
     *
     * @throws Throwable
     */
    public static function dataProviderTestThatProfileActionReturnsExpectedWithValidApiKeyToken(): Generator
    {
        $rolesService = self::getRolesService();

        #if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            foreach ($rolesService->getRoles() as $role) {
                yield [str_pad($rolesService->getShort($role), 40, '_'), $role];
            }
        #} else {
            yield [str_pad($rolesService->getShort(Role::LOGGED->value), 40, '_'), Role::LOGGED->value];
        #}
    }

    /**
     * @throws Throwable
     */
    private static function getRolesService(): RolesService
    {
        return self::getContainer()->get(RolesService::class);
    }
}
