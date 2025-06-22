<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Profile/GroupsControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Profile;

use App\Enum\Role;
use App\Security\RolesService;
use App\Tests\E2E\TestCase\WebTestCase;
use App\Tests\Utils\StringableArrayObject;
use App\Utils\JSON;
use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;
use Throwable;
use function array_map;
use function getenv;
use function property_exists;

/**
 * @package App\Tests\E2E\Controller\v1\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class GroupsControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/profile/groups';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/profile/groups` request returns `401` without Json Web Token')]
    public function testThatGroupsActionReturns401WithoutToken(): void
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
     * @throws JsonException
     */
    #[TestDox('Test that `GET /v1/profile/groups` request returns `401` with invalid API key token')]
    public function testThatGroupsActionReturns401WithInvalidApiKey(): void
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
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGroupsActionReturnExpected')]
    #[TestDox('Test that `GET /v1/profile/groups` request returns expected groups `$e` with `$u` + `$p`')]
    public function testThatGroupsActionReturnExpected(
        string $u,
        string $p,
        StringableArrayObject $e
    ): void {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        self::assertIsArray($responseContent);
        self::assertSame(
            $e->getArrayCopy(),
            array_map(
                static function (stdClass $userGroup): string {
                    self::assertTrue(property_exists($userGroup, 'role'));

                    $role = $userGroup->role;

                    self::assertIsObject($role);
                    self::assertTrue(property_exists($role, 'id'));

                    $id = $role->id;

                    self::assertIsString($id);

                    return $id;
                },
                $responseContent
            ),
        );
    }

    /**
     * @throws JsonException
     */
    #[DataProvider('dataProviderTestThatGroupsActionReturnExpectedWithValidApiKey')]
    #[TestDox('Test that `GET /v1/profile/groups` request returns `401` with valid `$token` API key token')]
    public function testThatGroupsActionReturnExpectedWithValidApiKey(string $token): void
    {
        $client = $this->getApiKeyClient($token);
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
     * @return Generator<array-key, array{0: string, 1: string, 2: StringableArrayObject}>
     */
    public static function dataProviderTestThatGroupsActionReturnExpected(): Generator
    {
        yield ['john', 'password', new StringableArrayObject([])];
        yield ['john-logged', 'password-logged', new StringableArrayObject(['ROLE_LOGGED'])];
        yield ['john-api', 'password-api', new StringableArrayObject(['ROLE_API'])];
        yield ['john-user', 'password-user', new StringableArrayObject(['ROLE_USER'])];
        yield ['john-admin', 'password-admin', new StringableArrayObject(['ROLE_ADMIN'])];
        yield ['john-root', 'password-root', new StringableArrayObject(['ROLE_ROOT'])];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password', new StringableArrayObject([])];
            yield ['john.doe-logged@test.com', 'password-logged', new StringableArrayObject(['ROLE_LOGGED'])];
            yield ['john.doe-api@test.com', 'password-api', new StringableArrayObject(['ROLE_API'])];
            yield ['john.doe-user@test.com', 'password-user', new StringableArrayObject(['ROLE_USER'])];
            yield ['john.doe-admin@test.com', 'password-admin', new StringableArrayObject(['ROLE_ADMIN'])];
            yield ['john.doe-root@test.com', 'password-root', new StringableArrayObject(['ROLE_ROOT'])];
        }
    }

    /**
     * @return Generator<array-key, array{0: string}>
     *
     * @throws Throwable
     */
    public static function dataProviderTestThatGroupsActionReturnExpectedWithValidApiKey(): Generator
    {
        $rolesService = self::getRolesService();

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            foreach ($rolesService->getRoles() as $role) {
                yield [str_pad($rolesService->getShort($role), 40, '_')];
            }
        } else {
            yield [str_pad($rolesService->getShort(Role::LOGGED->value), 40, '_')];
        }
    }

    /**
     * @throws Throwable
     */
    private static function getRolesService(): RolesService
    {
        return self::getContainer()->get(RolesService::class);
    }
}
