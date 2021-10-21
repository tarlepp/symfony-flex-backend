<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserGroupsControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\DataFixtures\ORM\LoadUserData;
use App\Security\Interfaces\RolesServiceInterface;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserGroupsControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupsControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/{id}/groups` returns HTTP status `401` for non-logged in user
     */
    public function testThatGetUserGroupsReturnsReturns401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl . '/' . LoadUserData::$uuids['john-user'] . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/{id}/groups` returns HTTP status `403` when using `$u` + `$p` credentials
     */
    public function testThatGetUserGroupsReturns403ForInvalidUser(string $userId, string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsActionsReturns200ForUserHimself
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/{id}/groups` returns expected `$expected` for user him/herself `$u` + `$p`
     */
    public function testThatGetUserGroupsActionsReturns200ForUserHimself(
        string $userId,
        string $u,
        string $p,
        string $expected
    ): void {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data = JSON::decode($content);

        $expected === ''
            ? self::assertEmpty($data)
            : self::assertSame($expected, $data[0]->role->id);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsReturns200ForRootRoleUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/{id}/groups` request returns expected `$expectedResponse` response
     */
    public function testThatGetUserGroupsReturns200ForRootRoleUser(
        string $userId,
        ?string $expectedResponse = null
    ): void {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data = JSON::decode($content);

        $expectedResponse === null
            ? self::assertEmpty($data)
            : self::assertSame($expectedResponse, $data[0]->role->id, $content);
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string}>
     */
    public function dataProviderTestThatGetUserGroupsReturns403ForInvalidUser(): Generator
    {
        yield [LoadUserData::$uuids['john-api'], 'john', 'password'];
        yield [LoadUserData::$uuids['john-user'], 'john-logged', 'password-logged'];
        yield [LoadUserData::$uuids['john-logged'], 'john-api', 'password-api'];
        yield [LoadUserData::$uuids['john-admin'], 'john-user', 'password-user'];
        yield [LoadUserData::$uuids['john'], 'john-admin', 'password-admin'];
        yield [LoadUserData::$uuids['john-api'], 'john.doe@test.com', 'password'];
        yield [LoadUserData::$uuids['john-user'], 'john.doe-logged@test.com', 'password-logged'];
        yield [LoadUserData::$uuids['john-logged'], 'john.doe-api@test.com', 'password-api'];
        yield [LoadUserData::$uuids['john-admin'], 'john.doe-user@test.com', 'password-user'];
        yield [LoadUserData::$uuids['john'], 'john.doe-admin@test.com', 'password-admin'];
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string, 3: string}>
     */
    public function dataProviderTestThatGetUserGroupsActionsReturns200ForUserHimself(): Generator
    {
        yield [LoadUserData::$uuids['john'], 'john', 'password', ''];
        yield [
            LoadUserData::$uuids['john-logged'], 
            'john-logged', 
            'password-logged', 
            RolesServiceInterface::ROLE_LOGGED,
        ];
        yield [LoadUserData::$uuids['john-api'], 'john-api', 'password-api', RolesServiceInterface::ROLE_API];
        yield [LoadUserData::$uuids['john-user'], 'john-user', 'password-user', RolesServiceInterface::ROLE_USER];
        yield [LoadUserData::$uuids['john-admin'], 'john-admin', 'password-admin', RolesServiceInterface::ROLE_ADMIN];
        yield [LoadUserData::$uuids['john-root'], 'john-root', 'password-root', RolesServiceInterface::ROLE_ROOT];
        yield [LoadUserData::$uuids['john'], 'john.doe@test.com', 'password', ''];
        yield [
            LoadUserData::$uuids['john-logged'],
            'john.doe-logged@test.com',
            'password-logged',
            RolesServiceInterface::ROLE_LOGGED,
        ];
        yield [
            LoadUserData::$uuids['john-api'], 
            'john.doe-api@test.com',
            'password-api', 
            RolesServiceInterface::ROLE_API,
        ];
        yield [
            LoadUserData::$uuids['john-user'], 
            'john.doe-user@test.com', 
            'password-user', 
            RolesServiceInterface::ROLE_USER,
        ];
        yield [
            LoadUserData::$uuids['john-admin'],
            'john.doe-admin@test.com',
            'password-admin',
            RolesServiceInterface::ROLE_ADMIN,
        ];
        yield [
            LoadUserData::$uuids['john-root'], 
            'john.doe-root@test.com', 
            'password-root', 
            RolesServiceInterface::ROLE_ROOT,
        ];
    }

    /**
     * @return Generator<array{0: string, 1: string|null}>
     */
    public function dataProviderTestThatGetUserGroupsReturns200ForRootRoleUser(): Generator
    {
        yield [LoadUserData::$uuids['john'], null];
        yield [LoadUserData::$uuids['john-api'], RolesServiceInterface::ROLE_API];
        yield [LoadUserData::$uuids['john-logged'], RolesServiceInterface::ROLE_LOGGED];
        yield [LoadUserData::$uuids['john-user'], RolesServiceInterface::ROLE_USER];
        yield [LoadUserData::$uuids['john-admin'], RolesServiceInterface::ROLE_ADMIN];
        yield [LoadUserData::$uuids['john-root'], RolesServiceInterface::ROLE_ROOT];
    }
}
