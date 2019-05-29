<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/UserGroupControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Controller;

use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function sprintf;

/**
 * Class UserGroupControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupControllerTest extends WebTestCase
{
    private $baseUrl = '/user_group';

    /**
     * @throws Throwable
     */
    public function testThatGetBaseRouteReturn403(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        unset($response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupUsersActionReturnsExpected
     *
     * @param int    $userCount
     * @param string $userGroupId
     *
     * @throws Throwable
     */
    public function testThatGetUserGroupUsersActionReturnsExpected(int $userCount, string $userGroupId): void
    {
        $client = $this->getClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userGroupId . '/users');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);

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
     * @throws Throwable
     */
    public function testThatAttachUserActionReturns403ForInvalidUser(string $username, string $password): void
    {
        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$container->get(UserGroupResource::class);

        /** @var UserResource $userResource */
        $userResource = static::$container->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => $username]);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = sprintf(
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
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent(),
            "Response:\n" . $response
        );

        unset($response, $client, $userGroup, $user, $userResource, $userGroupResource);
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserActionWorksAsExpected
     *
     * @param int $expectedStatus
     *
     * @throws Throwable
     */
    public function testThatAttachUserActionWorksAsExpected(int $expectedStatus): void
    {
        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$container->get(UserGroupResource::class);

        /** @var UserResource $userResource */
        $userResource = static::$container->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => 'john']);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = sprintf(
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
        static::assertSame($expectedStatus, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount(2, JSON::decode($response->getContent()));

        unset($response, $client, $userGroup, $user, $userResource, $userGroupResource);
    }

    /**
     * @depends testThatAttachUserActionWorksAsExpected
     * @throws Throwable
     */
    public function testThatDetachUserActionWorksAsExpected(): void
    {
        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$container->get(UserGroupResource::class);

        /** @var UserResource $userResource */
        $userResource = static::$container->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => 'john']);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = sprintf(
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
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

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
     * @throws Throwable
     */
    public function testThatDetachUserActionReturns403ForInvalidUser(string $username, string $password): void
    {
        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$container->get(UserGroupResource::class);

        /** @var UserResource $userResource */
        $userResource = static::$container->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => $username]);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);

        /** @noinspection NullPointerExceptionInspection */
        $url = sprintf(
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
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent(),
            "Response:\n" . $response
        );

        unset($response, $client, $userGroup, $user, $userResource, $userGroupResource);
    }

    /**
     * @return Generator
     *
     * @throws Throwable
     */
    public function dataProviderTestThatGetUserGroupUsersActionReturnsExpected(): Generator
    {
        static::bootKernel();

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$container->get(UserGroupResource::class);

        /** @noinspection NullPointerExceptionInspection */
        yield [1, $userGroupResource->findOneBy(['name' => 'Root users'])->getId()];
        yield [2, $userGroupResource->findOneBy(['name' => 'Admin users'])->getId()];
        yield [3, $userGroupResource->findOneBy(['name' => 'Normal users'])->getId()];
        yield [1, $userGroupResource->findOneBy(['name' => 'Api users'])->getId()];
        yield [5, $userGroupResource->findOneBy(['name' => 'Logged in users'])->getId()];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatAttachUserActionReturns403ForInvalidUser(): Generator
    {
        yield ['john',        'password'];
        yield ['john-api',    'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user',   'password-user'];
        yield ['john-admin',  'password-admin'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatAttachUserActionWorksAsExpected(): Generator
    {
        yield [201];
        yield [200];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatDetachUserActionReturns403ForInvalidUser(): Generator
    {
        return $this->dataProviderTestThatAttachUserActionReturns403ForInvalidUser();
    }
}
