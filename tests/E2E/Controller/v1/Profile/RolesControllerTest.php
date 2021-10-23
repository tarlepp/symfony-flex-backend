<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Profile/RolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Profile;

use App\Security\Interfaces\RolesServiceInterface;
use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\StringableArrayObject;
use App\Utils\Tests\WebTestCase;
use Generator;
use JsonException;
use Throwable;
use function str_pad;

/**
 * Class RolesControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RolesControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/profile/roles';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/profile/roles` request returns `401` without Json Web Token
     */
    public function testThatRolesActionReturns401WithoutToken(): void
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
     * @throws JsonException
     *
     * @testdox Test that `GET /v1/profile/roles` request returns `401` with invalid API Key token
     */
    public function testThatRolesActionReturns401WithInvalidApiKey(): void
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
     * @dataProvider dataProviderTestThatRolesActionReturnsExpected
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $e
     * @psalm-param StringableArrayObject $e
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/profile/roles` request returns expected `$e` roles with valid user `$u` + `$p`
     */
    public function testThatRolesActionReturnsExpected(string $u, string $p, StringableArrayObject $e): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertSame($e->getArrayCopy(), JSON::decode($content, true), $content);
    }

    /**
     * @dataProvider dataProviderTestThatRolesActionReturnsExpectedWithValidApiKey
     *
     * @throws JsonException
     *
     * @testdox Test that `GET /v1/profile/roles` request returns `401` with valid API key `$token` token
     */
    public function testThatRolesActionReturnsExpectedWithValidApiKey(string $token): void
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
     * @phpstan-return Generator<array{0: string, 1: string, 2: StringableArrayObject<array<int, string>>}>
     * @psalm-return Generator<array{0: string, 1: string, 2: StringableArrayObject}>
     */
    public function dataProviderTestThatRolesActionReturnsExpected(): Generator
    {
        yield [
            'john',
            'password',
            new StringableArrayObject([]),
        ];
        yield [
            'john-logged',
            'password-logged',
            new StringableArrayObject(['ROLE_LOGGED']),
        ];
        yield [
            'john-api',
            'password-api',
            new StringableArrayObject(['ROLE_API', 'ROLE_LOGGED']),
        ];
        yield [
            'john-user',
            'password-user',
            new StringableArrayObject(['ROLE_USER', 'ROLE_LOGGED']),
        ];
        yield [
            'john-admin',
            'password-admin',
            new StringableArrayObject(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']),
        ];
        yield [
            'john-root',
            'password-root',
            new StringableArrayObject(['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']),
        ];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield [
                'john.doe@test.com',
                'password',
                new StringableArrayObject([]),
            ];
            yield [
                'john.doe-logged@test.com',
                'password-logged',
                new StringableArrayObject(['ROLE_LOGGED']),
            ];
            yield [
                'john.doe-api@test.com',
                'password-api',
                new StringableArrayObject(['ROLE_API', 'ROLE_LOGGED']),
            ];
            yield [
                'john.doe-user@test.com',
                'password-user',
                new StringableArrayObject(['ROLE_USER', 'ROLE_LOGGED']),
            ];
            yield [
                'john.doe-admin@test.com',
                'password-admin',
                new StringableArrayObject(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']),
            ];
            yield [
                'john.doe-root@test.com',
                'password-root',
                new StringableArrayObject(['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']),
            ];
        }
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatRolesActionReturnsExpectedWithValidApiKey(): Generator
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
