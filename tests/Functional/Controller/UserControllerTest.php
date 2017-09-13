<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/UserControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Entity\User;
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

    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());
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
     */
    public function testThatCountActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertJsonStringEqualsJsonString('{"count":5}', $response->getContent());
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @param string $username
     * @param string $password
     */
    public function testThatCountActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode());
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
     */
    public function testThatFindActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertCount(5, JSON::decode($response->getContent()));
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @param string $username
     * @param string $password
     */
    public function testThatFindActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode());
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
     */
    public function testThatIdsActionReturnExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertCount(5, JSON::decode($response->getContent()));
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @param string $username
     * @param string $password
     */
    public function testThatIdsActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode());
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @return string
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
        static::assertSame(201, $response->getStatusCode());

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
        static::assertSame(403, $response->getStatusCode());
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
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        $data['id'] = $userId;

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
        static::assertSame(403, $response->getStatusCode());
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
        static::assertSame(403, $response->getStatusCode());
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent()
        );
    }

    /**
     * @depends testThatUpdateActionWorksLikeExpected
     *
     * @param string $userId
     */
    public function testThatDeleteActionWorksLikeExpected(string $userId): void
    {
        $client = $this->getClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . $userId);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent());
    }

    public function testThatDeleteActionThrowsAnExceptionIfUserTriesToRemoveHimself(): void
    {
        self::bootKernel();

        /** @var UserResource $userResource */
        $userResource = static::$kernel->getContainer()->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => 'john-root'], null, true);

        $client = $this->getClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . $user->getId());

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(400, $response->getStatusCode(), $response->getContent());
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
     */
    public function testThatGetRolesActionsReturns403ForInvalidUser(
        string $username,
        string $password,
        array $userIds
    ): void {
        $client = $this->getClient($username, $password);

        foreach ($userIds as $userId) {
            $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

            $response = $client->getResponse();

            static::assertInstanceOf(Response::class, $response);
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
     */
    public function testThatGetRolesActionsReturns200ForUserHimself(
        string $username,
        string $password,
        string $userId,
        string $expectedResponse
    ): void {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent());
        static::assertJsonStringEqualsJsonString($expectedResponse, $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesActionReturns200ForRootRoleUser
     *
     * @param string $userId
     * @param string $expectedResponse
     */
    public function testThatGetRolesActionReturns200ForRootRoleUser(string $userId, string $expectedResponse): void
    {
        $client = $this->getClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent());
        static::assertJsonStringEqualsJsonString($expectedResponse, $response->getContent());
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
    public function dataProviderInvalidUsers(): array
    {
        return [
            ['john',        'password'],
            ['john-logged', 'password-logged'],
            ['john-user',   'password-user'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderInvalidUsersCreate(): array
    {
        return [
            ['john',        'password'],
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
        $rolesService = static::$kernel->getContainer()->get(RolesService::class);

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
        $rolesService = static::$kernel->getContainer()->get(RolesService::class);

        $output = [];

        foreach ($userResource->find() as $user) {
            $user->setRolesService($rolesService);

            $output[] = [$user->getId(), JSON::encode($user->getRoles())];
        }

        return $output;
    }
}
