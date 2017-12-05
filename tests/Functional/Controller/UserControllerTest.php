<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/UserControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserControllerTest
 *
 * @package App\Tests\Functional\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserControllerTest extends WebTestCase
{
    private $baseUrl = '/user';

    /**
     * @throws \Exception
     */
    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"JWT Token not found","code":401}',
            $response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatCountActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString('{"count":6}', $response->getContent());
    }

    /**
     * @dataProvider dataProviderValidApiKeyUsers
     *
     * @param string $role
     */
    public function testThatCountActionReturnsExpectedForApiKeyUser(string $role): void
    {
        $client = $this->getApiKeyClient($role);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString('{"count":6}', $response->getContent());
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatCountActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderInvalidApiKeyUsers
     *
     * @param string $role
     */
    public function testThatCountActionReturns403ForInvalidApiKeyUser(string $role): void
    {
        $client = $this->getApiKeyClient($role);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatFindActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount(6, JSON::decode($response->getContent()));
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatFindActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatIdsActionReturnExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount(6, JSON::decode($response->getContent()));
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatIdsActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function testThatCreateActionWorksLikeExpected(): string
    {
        $data = [
            'username'  => 'test-user',
            'firstname' => 'test',
            'surname'   => 'user',
            'email'     => 'test-user@test.com',
            'password'  => 'some password',
        ];

        $client = $this->getClient('john-root', 'password-root');
        $client->request('POST', $this->baseUrl, [], [], [], JSON::encode($data));

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(201, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseData = $response->getContent();

        $data['id'] = JSON::decode($responseData)->id;

        unset($data['password']);

        static::assertJsonStringEqualsJsonString(JSON::encode($data), $responseData);

        return $data['id'];
    }

    /**
     * @dataProvider dataProviderInvalidUsersCreate
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatCreateActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $data = [
            'username'  => 'test-user',
            'firstname' => 'test',
            'surname'   => 'user',
            'email'     => 'test-user@test.com',
        ];

        $client = $this->getClient($username, $password);
        $client->request('POST', $this->baseUrl, [], [], [], JSON::encode($data));

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @depends testThatCreateActionWorksLikeExpected
     *
     * @param string $userId
     *
     * @return string
     *
     * @throws \Exception
     */
    public function testThatUpdateActionWorksLikeExpected(string $userId): string
    {
        $data = [
            'username'  => 'test-user',
            'firstname' => 'test-1',
            'surname'   => 'user-2',
            'email'     => 'test-user@test.com',
        ];

        $client = $this->getClient('john-root', 'password-root');
        $client->request('PUT', $this->baseUrl . '/' . $userId, [], [], [], JSON::encode($data));

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        $data['id'] = $userId;

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(JSON::encode($data), $response->getContent());

        return $userId;
    }

    /**
     * @depends      testThatUpdateActionWorksLikeExpected
     * @dataProvider dataProviderInvalidUsersCreate
     *
     * @param string $username
     * @param string $password
     * @param string $userId
     *
     * @return string
     *
     * @throws \Exception
     */
    public function testThatUpdateActionReturns403ForInvalidUser(
        string $username,
        string $password,
        string $userId
    ): string {
        $data = [
            'username'  => 'test-user',
            'firstname' => 'test-1',
            'surname'   => 'user-2',
            'email'     => 'test-user@test.com',
        ];

        $client = $this->getClient($username, $password);
        $client->request('PUT', $this->baseUrl . '/' . $userId, [], [], [], JSON::encode($data));

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );

        return $userId;
    }

    /**
     * @depends      testThatUpdateActionWorksLikeExpected
     * @dataProvider dataProviderInvalidUsersCreate
     *
     * @param string $username
     * @param string $password
     * @param string $userId
     *
     * @throws \Exception
     */
    public function testThatDeleteActionReturns403ForInvalidUser(
        string $username,
        string $password,
        string $userId
    ): void {
        $client = $this->getClient($username, $password);
        $client->request('DELETE', $this->baseUrl . '/' . $userId);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @depends testThatUpdateActionWorksLikeExpected
     *
     * @param string $userId
     *
     * @throws \Exception
     */
    public function testThatDeleteActionWorksLikeExpected(string $userId): void
    {
        $client = $this->getClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . $userId);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testThatDeleteActionThrowsAnExceptionIfUserTriesToRemoveHimself(): void
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => 'john-root'], null, true);

        $client = $this->getClient('john-root', 'password-root');

        /** @noinspection NullPointerExceptionInspection */
        $client->request('DELETE', $this->baseUrl . '/' . $user->getId());

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(400, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"You cannot remove yourself...","code":0,"status":400}',
            $response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesActionsReturns403ForInvalidUser
     *
     * @param string $username
     * @param string $password
     * @param array  $userIds
     *
     * @throws \Exception
     */
    public function testThatGetUserRolesActionsReturns403ForInvalidUser(
        string $username,
        string $password,
        array $userIds
    ): void {
        $client = $this->getClient($username, $password);

        foreach ($userIds as $userId) {
            $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

            $response = $client->getResponse();

            static::assertInstanceOf(Response::class, $response);

            /** @noinspection NullPointerExceptionInspection */
            static::assertSame(403, $response->getStatusCode(), $response->getContent());
        }
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesActionsReturns200ForUserHimself
     *
     * @param string $username
     * @param string $password
     * @param string $userId
     * @param string $expectedResponse
     *
     * @throws \Exception
     */
    public function testThatGetUserRolesActionsReturns200ForUserHimself(
        string $username,
        string $password,
        string $userId,
        string $expectedResponse
    ): void {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString($expectedResponse, $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesActionReturns200ForRootRoleUser
     *
     * @param string $userId
     * @param string $expectedResponse
     *
     * @throws \Exception
     */
    public function testThatGetUserRolesActionReturns200ForRootRoleUser(string $userId, string $expectedResponse): void
    {
        $client = $this->getClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString($expectedResponse, $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsActionsReturns403ForInvalidUser
     *
     * @param string $username
     * @param string $password
     * @param array  $userIds
     *
     * @throws \Exception
     */
    public function testThatGetUserGroupsActionsReturns403ForInvalidUser(
        string $username,
        string $password,
        array $userIds
    ): void {
        $client = $this->getClient($username, $password);

        foreach ($userIds as $userId) {
            $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

            $response = $client->getResponse();

            static::assertInstanceOf(Response::class, $response);

            /** @noinspection NullPointerExceptionInspection */
            static::assertSame(403, $response->getStatusCode(), $response->getContent());
        }
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsActionsReturns200ForUserHimself
     *
     * @param string $username
     * @param string $password
     * @param string $expectedResponse
     * @param string $userId
     *
     * @throws \Exception
     */
    public function testThatGetUserGroupsActionsReturns200ForUserHimself(
        string $username,
        string $password,
        string $expectedResponse = null,
        string $userId
    ): void {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        $data = JSON::decode($response->getContent());

        if ($expectedResponse === null) {
            static::assertEmpty($data);
        } else {
            static::assertSame($expectedResponse, $data[0]->role->id);
        }
    }

    /**
     * @dataProvider dataProviderInvalidUsersCreate
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatAttachUserGroupActionReturns403ForInvalidUser(string $username, string $password): void
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$kernel->getContainer()->get(UserGroupResource::class);

        $user = $userResource->findOneBy(['username' => $username]);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = \sprintf(
            '%s/%s/group/%s',
            $this->baseUrl,
            $user->getId(),
            $userGroup->getId()
        );

        $client = $this->getClient($username, $password);
        $client->request('POST', $url);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserGroupActionWorksAsExpected
     *
     * @param int $expectedStatus
     *
     * @throws \Exception
     */
    public function testThatAttachUserGroupActionWorksAsExpected(int $expectedStatus): void
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$kernel->getContainer()->get(UserGroupResource::class);

        $user = $userResource->findOneBy(['username' => 'john']);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = \sprintf(
            '%s/%s/group/%s',
            $this->baseUrl,
            $user->getId(),
            $userGroup->getId()
        );

        $client = $this->getClient('john-root', 'password-root');
        $client->request('POST', $url);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame($expectedStatus, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount(1, JSON::decode($response->getContent()));
    }

    /**
     * @depends testThatAttachUserGroupActionWorksAsExpected
     *
     * @throws \Exception
     */
    public function testThatDetachUserGroupActionWorksAsExpected(): void
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$kernel->getContainer()->get(UserGroupResource::class);

        $user = $userResource->findOneBy(['username' => 'john']);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = \sprintf(
            '%s/%s/group/%s',
            $this->baseUrl,
            $user->getId(),
            $userGroup->getId()
        );

        $client = $this->getClient('john-root', 'password-root');
        $client->request('DELETE', $url);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount(0, JSON::decode($response->getContent()));
    }

    /**
     * @depends      testThatDetachUserGroupActionWorksAsExpected
     *
     * @dataProvider dataProviderInvalidUsersCreate
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatDetachUserGroupActionReturns403ForInvalidUser(string $username, string $password): void
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$kernel->getContainer()->get(UserGroupResource::class);

        $user = $userResource->findOneBy(['username' => $username]);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = \sprintf(
            '%s/%s/group/%s',
            $this->baseUrl,
            $user->getId(),
            $userGroup->getId()
        );

        $client = $this->getClient($username, $password);
        $client->request('DELETE', $url);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(403, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupsActionReturns200ForRootRoleUser
     *
     * depends testThatDetachUserGroupActionWorksAsExpected
     *
     * @param string $userId
     * @param string $expectedResponse
     *
     * @throws \Exception
     */
    public function testThatGetUserGroupsActionReturns200ForRootRoleUser(
        string $userId,
        string $expectedResponse = null
    ): void {
        $client = $this->getClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userId . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        $data = JSON::decode($response->getContent());

        if ($expectedResponse === null) {
            static::assertEmpty($data);
        } else {
            /** @noinspection NullPointerExceptionInspection */
            static::assertSame($expectedResponse, $data[0]->role->id, $response->getContent());
        }
    }

    /**
     * @return array
     */
    public function dataProviderValidUsers(): array
    {
        return [
            ['john-admin',  'password-admin'],
            ['john-root',   'password-root'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderValidApiKeyUsers(): array
    {
        return [
            ['admin'],
            ['root'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderInvalidUsers(): array
    {
        return [
            ['john',        'password'],
            ['john-api',    'password-api'],
            ['john-logged', 'password-logged'],
            ['john-user',   'password-user'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderInvalidApiKeyUsers(): array
    {
        return [
            ['api'],
            ['logged'],
            ['user'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatAttachUserGroupActionWorksAsExpected(): array
    {
        return [
            [201],
            [200],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderInvalidUsersCreate(): array
    {
        return [
            ['john',        'password'],
            ['john-api',    'password-api'],
            ['john-logged', 'password-logged'],
            ['john-user',   'password-user'],
            ['john-admin',  'password-admin'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetRolesActionsReturns403ForInvalidUser(): array
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        $users = $userResource->find();

        $iterator = function (array $userData) use ($users): array {
            $ids = [];

            foreach ($users as $user) {
                if ($user->getUsername() === $userData[0]) {
                    continue;
                }

                $ids[] = $user->getId();
            }

            $userData[] = $ids;

            return $userData;
        };

        return \array_map($iterator, $this->dataProviderInvalidUsersCreate());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetRolesActionsReturns200ForUserHimself(): array
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        /** @var RolesService $rolesService */
        $rolesService = static::$kernel->getContainer()->get('test.service_locator')->get(RolesService::class);

        $users = $userResource->find();

        $iterator = function (array $userData) use ($users, $rolesService): array {
            /** @var User $user */
            $user = \array_values(
                \array_filter(
                    $users,
                    function (User $user) use ($userData) {
                        return $userData[0] === $user->getUsername();
                    }
                )
            )[0];

            $user->setRolesService($rolesService);

            $userData[] = $user->getId();
            $userData[] = JSON::encode($user->getRoles());

            return $userData;
        };

        $credentials = [
            ['john',        'password'],
            ['john-api',    'password-api'],
            ['john-logged', 'password-logged'],
            ['john-user',   'password-user'],
            ['john-admin',  'password-admin'],
            ['john-root',   'password-root'],
        ];

        return \array_map($iterator, $credentials);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetRolesActionReturns200ForRootRoleUser(): array
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        /** @var RolesService $rolesService */
        $rolesService = static::$kernel->getContainer()->get('test.service_locator')->get(RolesService::class);

        $output = [];

        foreach ($userResource->find() as $user) {
            $user->setRolesService($rolesService);

            $output[] = [$user->getId(), JSON::encode($user->getRoles())];
        }

        return $output;
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetUserGroupsActionsReturns403ForInvalidUser(): array
    {
        return $this->dataProviderTestThatGetRolesActionsReturns403ForInvalidUser();
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetUserGroupsActionsReturns200ForUserHimself(): array
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        $users = $userResource->find();

        $iterator = function (array $userData) use ($users): array {
            /** @var User $user */
            $user = \array_values(
                \array_filter(
                    $users,
                    function (User $user) use ($userData) {
                        return $userData[0] === $user->getUsername();
                    }
                )
            )[0];

            $userData[] = $user->getId();

            return $userData;
        };

        $credentials = [
            ['john',        'password',         null],
            ['john-api',    'password-api',     'ROLE_API'],
            ['john-logged', 'password-logged',  'ROLE_LOGGED'],
            ['john-user',   'password-user',    'ROLE_USER'],
            ['john-admin',  'password-admin',   'ROLE_ADMIN'],
            ['john-root',   'password-root',    'ROLE_ROOT'],
        ];

        return \array_map($iterator, $credentials);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetUserGroupsActionReturns200ForRootRoleUser(): array
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        $output = [];

        foreach ($userResource->find() as $user) {
            $output[] = [$user->getId(), \count($user->getRoles()) ? $user->getRoles()[0] : null];
        }

        return $output;
    }
}
