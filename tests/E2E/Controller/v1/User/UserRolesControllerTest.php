<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserRolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\DataFixtures\ORM\LoadUserData;
use App\Security\Interfaces\RolesServiceInterface;
use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserRolesControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserRolesControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/{id}/roles` request returns `401` for non-logged in user
     */
    public function testThatGetUserRolesReturnsReturns401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl . '/' . LoadUserData::$uuids['john-user'] . '/roles');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesActionsReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/$id/roles` request returns `403` when using invalid user `$u` + `$p`
     */
    public function testThatGetUserRolesReturns403ForInvalidUser(string $id, string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/' . $id . '/roles');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserRolesReturns200ForUserHimself
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/$id/roles` request returns `$e` for user `$u` + `$p` him/herself
     */
    public function testThatGetUserRolesReturns200ForUserHimself(string $id, string $u, string $p, string $e): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/' . $id . '/roles');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertJsonStringEqualsJsonString($e, $content);
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesReturns200ForRootRoleUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/$id/roles` request returns expected `$e` for root user
     */
    public function testThatGetUserRolesReturns200ForRootRoleUser(string $id, string $e): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $id . '/roles');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertJsonStringEqualsJsonString($e, $content);
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string}>
     */
    public function dataProviderTestThatGetRolesActionsReturns403ForInvalidUser(): Generator
    {
        yield [LoadUserData::$uuids['john-api'], 'john', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield [LoadUserData::$uuids['john-user'], 'john-logged', 'password-logged'];
            yield [LoadUserData::$uuids['john-admin'], 'john-user', 'password-user'];
            yield [LoadUserData::$uuids['john-logged'], 'john-api', 'password-api'];
            yield [LoadUserData::$uuids['john'], 'john-admin', 'password-admin'];
        }

        yield [LoadUserData::$uuids['john-api'], 'john.doe@test.com', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield [LoadUserData::$uuids['john-user'], 'john.doe-logged@test.com', 'password-logged'];
            yield [LoadUserData::$uuids['john-admin'], 'john.doe-user@test.com', 'password-user'];
            yield [LoadUserData::$uuids['john-logged'], 'john.doe-api@test.com', 'password-api'];
            yield [LoadUserData::$uuids['john'], 'john.doe-admin@test.com', 'password-admin'];
        }
    }

    /**
     * @throws Throwable
     *
     * @return Generator<array{0: string, 1: string, 2: string, 3: string}>
     */
    public function dataProviderTestThatGetUserRolesReturns200ForUserHimself(): Generator
    {
        $RolesServiceInterface = self::getContainer()->get(RolesService::class);

        yield [
            LoadUserData::$uuids['john'],
            'john',
            'password',
            '[]',
        ];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield [
                LoadUserData::$uuids['john-api'],
                'john-api',
                'password-api',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_API])),
            ];

            yield [
                LoadUserData::$uuids['john-logged'],
                'john-logged',
                'password-logged',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_LOGGED])),
            ];

            yield [
                LoadUserData::$uuids['john-user'],
                'john-user',
                'password-user',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_USER])),
            ];

            yield [
                LoadUserData::$uuids['john-admin'],
                'john-admin',
                'password-admin',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_ADMIN])),
            ];

            yield [
                LoadUserData::$uuids['john-root'],
                'john-root',
                'password-root',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_ROOT])),
            ];
        }

        yield [
            LoadUserData::$uuids['john'],
            'john.doe@test.com',
            'password',
            '[]',
        ];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield [
                LoadUserData::$uuids['john-api'],
                'john.doe-api@test.com',
                'password-api',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_API])),
            ];

            yield [
                LoadUserData::$uuids['john-logged'],
                'john.doe-logged@test.com',
                'password-logged',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_LOGGED])),
            ];

            yield [
                LoadUserData::$uuids['john-user'],
                'john.doe-user@test.com',
                'password-user',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_USER])),
            ];

            yield [
                LoadUserData::$uuids['john-admin'],
                'john.doe-admin@test.com',
                'password-admin',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_ADMIN])),
            ];

            yield [
                LoadUserData::$uuids['john-root'],
                'john.doe-root@test.com',
                'password-root',
                JSON::encode($RolesServiceInterface->getInheritedRoles([RolesServiceInterface::ROLE_ROOT])),
            ];
        }
    }

    /**
     * @throws Throwable
     *
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetRolesReturns200ForRootRoleUser(): Generator
    {
        $rolesService = self::getContainer()->get(RolesServiceInterface::class);

        yield [LoadUserData::$uuids['john'], '[]'];

        yield [
            LoadUserData::$uuids['john-api'],
            JSON::encode($rolesService->getInheritedRoles([RolesServiceInterface::ROLE_API])),
        ];

        yield [
            LoadUserData::$uuids['john-logged'],
            JSON::encode($rolesService->getInheritedRoles([RolesServiceInterface::ROLE_LOGGED])),
        ];

        yield [
            LoadUserData::$uuids['john-user'],
            JSON::encode($rolesService->getInheritedRoles([RolesServiceInterface::ROLE_USER])),
        ];

        yield [
            LoadUserData::$uuids['john-admin'],
            JSON::encode($rolesService->getInheritedRoles([RolesServiceInterface::ROLE_ADMIN])),
        ];

        yield [
            LoadUserData::$uuids['john-root'],
            JSON::encode($rolesService->getInheritedRoles([RolesServiceInterface::ROLE_ROOT])),
        ];
    }
}
