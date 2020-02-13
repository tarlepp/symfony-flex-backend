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
    private string $baseUrl = '/user_group';

    /**
     * @throws Throwable
     */
    public function testThatGetBaseRouteReturn403(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        /** @var Response $response */
        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserGroupUsersActionReturnsExpected
     *
     * @param int    $userCount
     * @param string $userGroupId
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /user_group/$userGroupId/users` returns expected count $userCount of users
     */
    public function testThatGetUserGroupUsersActionReturnsExpected(int $userCount, string $userGroupId): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userGroupId . '/users');

        /** @var Response $response */
        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
        static::assertCount($userCount, JSON::decode($response->getContent()));
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserActionReturns403ForInvalidUser
     *
     * @param string $username
     * @param string $password
     *
     * @throws Throwable
     *
     * @testdox Test that invalid user $username + $password cannot attach user to existing user group.
     */
    public function testThatAttachUserActionReturns403ForInvalidUser(string $username, string $password): void
    {
        /**
         * @var UserGroupResource $userGroupResource
         * @var UserResource      $userResource
         */
        $userGroupResource = static::$container->get(UserGroupResource::class);
        $userResource = static::$container->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => $username]);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);
        $url = sprintf(
            '%s/%s/user/%s',
            $this->baseUrl,
            $userGroup->getId(),
            $user->getId()
        );

        $client = $this->getTestClient($username, $password);
        $client->request('POST', $url);

        /** @var Response $response */
        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent(),
            "Response:\n" . $response
        );
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserActionWorksAsExpected
     *
     * @param int $expectedStatus
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /user_group/_ug_id_/user/_u_id_` returns HTTP status $expectedStatus
     */
    public function testThatAttachUserActionWorksAsExpected(int $expectedStatus): void
    {
        /**
         * @var UserGroupResource $userGroupResource
         * @var UserResource      $userResource
         */
        $userGroupResource = static::$container->get(UserGroupResource::class);
        $userResource = static::$container->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => 'john']);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);
        $url = sprintf(
            '%s/%s/user/%s',
            $this->baseUrl,
            $userGroup->getId(),
            $user->getId()
        );

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('POST', $url);

        /** @var Response $response */
        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame($expectedStatus, $response->getStatusCode(), "Response:\n" . $response);
        static::assertCount(2, JSON::decode($response->getContent()));
    }

    /**
     * @depends testThatAttachUserActionWorksAsExpected
     * @throws Throwable
     */
    public function testThatDetachUserActionWorksAsExpected(): void
    {
        /**
         * @var UserGroupResource $userGroupResource
         * @var UserResource      $userResource
         */
        $userGroupResource = static::$container->get(UserGroupResource::class);
        $userResource = static::$container->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => 'john']);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);
        $url = sprintf(
            '%s/%s/user/%s',
            $this->baseUrl,
            $userGroup->getId(),
            $user->getId()
        );

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $url);

        /** @var Response $response */
        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
        static::assertCount(1, JSON::decode($response->getContent()));
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
     *
     * @testdox Test that `DELETE /user_group/_ug_id_/user/_u_id_` with $username + password returns HTTP status 403
     */
    public function testThatDetachUserActionReturns403ForInvalidUser(string $username, string $password): void
    {
        /**
         * @var UserGroupResource $userGroupResource
         * @var UserResource      $userResource
         */
        $userGroupResource = static::$container->get(UserGroupResource::class);
        $userResource = static::$container->get(UserResource::class);

        $user = $userResource->findOneBy(['username' => $username]);
        $userGroup = $userGroupResource->findOneBy(['name' => 'Root users']);
        $url = sprintf(
            '%s/%s/user/%s',
            $this->baseUrl,
            $userGroup->getId(),
            $user->getId()
        );

        $client = $this->getTestClient($username, $password);
        $client->request('DELETE', $url);

        /** @var Response $response */
        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent(),
            "Response:\n" . $response
        );
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
        //yield ['john',        'password'];
        //yield ['john-api',    'password-api'];
        //yield ['john-logged', 'password-logged'];
        //yield ['john-user',   'password-user'];
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
