<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/UserGroupControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserGroupControllerTest
 *
 * @package App\Tests\Functional\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupControllerTest extends WebTestCase
{
    private $baseUrl = '/user_group';

    /**
     * @throws \Exception
     */
    public function testThatGetBaseRouteReturn403(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        unset($response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupUsersActionReturnsExpected
     *
     * @param int    $userCount
     * @param string $userGroupId
     *
     * @throws \Exception
     */
    public function testThatGetUserGroupUsersActionReturnsExpected(int $userCount, string $userGroupId): void
    {
        $client = $this->getClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userGroupId . '/users');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount($userCount, JSON::decode($response->getContent()));

        unset($response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserActionReturns403ForInvalidUser
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatAttachUserActionReturns403ForInvalidUser(string $username, string $password): void
    {
        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = $this->testContainer->get(UserGroupResource::class);

        /** @var UserResource $userResource */
        $userResource = $this->testContainer->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => $username]);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = \sprintf(
            '%s/%s/user/%s',
            $this->baseUrl,
            $userGroup->getId(),
            $user->getId()
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

        unset($response, $client, $userGroup, $user, $userResource, $userGroupResource);
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserActionWorksAsExpected
     *
     * @param int $expectedStatus
     *
     * @throws \Exception
     */
    public function testThatAttachUserActionWorksAsExpected(int $expectedStatus): void
    {
        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = $this->testContainer->get(UserGroupResource::class);

        /** @var UserResource $userResource */
        $userResource = $this->testContainer->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => 'john']);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = \sprintf(
            '%s/%s/user/%s',
            $this->baseUrl,
            $userGroup->getId(),
            $user->getId()
        );

        $client = $this->getClient('john-root', 'password-root');
        $client->request('POST', $url);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame($expectedStatus, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount(2, JSON::decode($response->getContent()));

        unset($response, $client, $userGroup, $user, $userResource, $userGroupResource);
    }

    /**
     * @depends testThatAttachUserActionWorksAsExpected
     * @throws \Exception
     */
    public function testThatDetachUserActionWorksAsExpected(): void
    {
        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = $this->testContainer->get(UserGroupResource::class);

        /** @var UserResource $userResource */
        $userResource = $this->testContainer->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => 'john']);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = \sprintf(
            '%s/%s/user/%s',
            $this->baseUrl,
            $userGroup->getId(),
            $user->getId()
        );

        $client = $this->getClient('john-root', 'password-root');
        $client->request('DELETE', $url);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount(1, JSON::decode($response->getContent()));

        unset($response, $client, $userGroup, $user, $userResource, $userGroupResource);
    }

    /**
     * @depends      testThatDetachUserActionWorksAsExpected
     *
     * @dataProvider dataProviderTestThatDetachUserActionReturns403ForInvalidUser
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatDetachUserActionReturns403ForInvalidUser(string $username, string $password): void
    {
        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = $this->testContainer->get(UserGroupResource::class);

        /** @var UserResource $userResource */
        $userResource = $this->testContainer->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => $username]);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = \sprintf(
            '%s/%s/user/%s',
            $this->baseUrl,
            $userGroup->getId(),
            $user->getId()
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

        unset($response, $client, $userGroup, $user, $userResource, $userGroupResource);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetUserGroupUsersActionReturnsExpected(): array
    {
        self::bootKernel();

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$kernel->getContainer()->get(UserGroupResource::class);

        /** @noinspection NullPointerExceptionInspection */
        return [
            [1, $userGroupResource->findOneBy(['name' => 'Root users'])->getId()],
            [2, $userGroupResource->findOneBy(['name' => 'Admin users'])->getId()],
            [3, $userGroupResource->findOneBy(['name' => 'Normal users'])->getId()],
            [1, $userGroupResource->findOneBy(['name' => 'Api users'])->getId()],
            [5, $userGroupResource->findOneBy(['name' => 'Logged in users'])->getId()],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatAttachUserActionReturns403ForInvalidUser(): array
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
    public function dataProviderTestThatAttachUserActionWorksAsExpected(): array
    {
        return [
            [201],
            [200],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatDetachUserActionReturns403ForInvalidUser(): array
    {
        return $this->dataProviderTestThatAttachUserActionReturns403ForInvalidUser();
    }
}
