<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/User/UserGroupsControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\User;

use App\DataFixtures\ORM\LoadUserData;
use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserGroupsControllerTest
 *
 * @package App\Tests\E2E\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupsControllerTest extends WebTestCase
{
    private string $baseUrl = '/user';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /user/{user}/groups` returns 401 for non-logged in user
     */
    public function testThatGetUserGroupsReturnsReturns401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl . '/' . LoadUserData::$uuids['john-user'] . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /user/$userId/groups` returns 403 with invalid user $username + $password
     */
    public function testThatGetUserGroupsReturns403ForInvalidUser(
        string $userId,
        string $username,
        string $password
    ): void {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsActionsReturns200ForUserHimself
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /user/$userId/groups` returns expected with user him/herself $username + $password
     */
    public function testThatGetUserGroupsActionsReturns200ForUserHimself(
        string $userId,
        string $username,
        string $password,
        string $expectedResponse
    ): void {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data = JSON::decode($content);

        $expectedResponse === ''
            ? static::assertEmpty($data)
            : static::assertSame($expectedResponse, $data[0]->role->id);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsReturns200ForRootRoleUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /user/$userId/groups` request returns expected response `$expectedResponse`
     */
    public function testThatGetUserGroupsReturns200ForRootRoleUser(
        string $userId,
        ?string $expectedResponse = null
    ): void {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data = JSON::decode($content);

        $expectedResponse === null
            ? static::assertEmpty($data)
            : static::assertSame($expectedResponse, $data[0]->role->id, $content);
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string}>
     */
    public function dataProviderTestThatGetUserGroupsReturns403ForInvalidUser(): Generator
    {
        yield [LoadUserData::$uuids['john-api'], 'john', 'password'];
        yield [LoadUserData::$uuids['john-logged'], 'john-api', 'password-api'];
        yield [LoadUserData::$uuids['john-user'], 'john-logged', 'password-logged'];
        yield [LoadUserData::$uuids['john-admin'], 'john-user', 'password-user'];
        yield [LoadUserData::$uuids['john'], 'john-admin', 'password-admin'];
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string, 3: string}>
     */
    public function dataProviderTestThatGetUserGroupsActionsReturns200ForUserHimself(): Generator
    {
        yield [LoadUserData::$uuids['john'], 'john', 'password', ''];
        yield [LoadUserData::$uuids['john-api'], 'john-api', 'password-api', RolesService::ROLE_API];
        yield [LoadUserData::$uuids['john-logged'], 'john-logged', 'password-logged', RolesService::ROLE_LOGGED];
        yield [LoadUserData::$uuids['john-user'], 'john-user', 'password-user', RolesService::ROLE_USER];
        yield [LoadUserData::$uuids['john-admin'], 'john-admin', 'password-admin', RolesService::ROLE_ADMIN];
        yield [LoadUserData::$uuids['john-root'], 'john-root', 'password-root', RolesService::ROLE_ROOT];
    }

    /**
     * @return Generator<array{0: string, 1: string|null}>
     */
    public function dataProviderTestThatGetUserGroupsReturns200ForRootRoleUser(): Generator
    {
        yield [LoadUserData::$uuids['john'], null];
        yield [LoadUserData::$uuids['john-api'], RolesService::ROLE_API];
        yield [LoadUserData::$uuids['john-logged'], RolesService::ROLE_LOGGED];
        yield [LoadUserData::$uuids['john-user'], RolesService::ROLE_USER];
        yield [LoadUserData::$uuids['john-admin'], RolesService::ROLE_ADMIN];
        yield [LoadUserData::$uuids['john-root'], RolesService::ROLE_ROOT];
    }
}
