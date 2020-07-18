<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/User/UserUpdateInvalidTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\User;

use App\DataFixtures\ORM\LoadUserData;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class UserUpdateInvalidTest
 *
 * @package App\Tests\E2E\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserUpdateInvalidUserTest extends WebTestCase
{
    /**
     * @dataProvider dataProviderTestThatPutActionReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `PUT /user/{userId}` returns HTTP status 403 with invalid user $username + $password
     */
    public function testThatPutActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $data = [
            'username' => 'test-user',
            'firstName' => 'test-1',
            'lastName' => 'user-2',
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient($username, $password);
        $client->request('PUT', '/user/' . LoadUserData::$uuids['john'], [], [], [], JSON::encode($data));

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
     * @dataProvider dataProviderTestThatPatchActionReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `PATCH /user/{userId}` returns HTTP status 403 with invalid user $username + $password
     */
    public function testThatPatchActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $data = [
            'username' => 'test-user',
            'firstName' => 'test-1',
            'lastName' => 'user-2',
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient($username, $password);
        $client->request('PUT', '/user/' . LoadUserData::$uuids['john'], [], [], [], JSON::encode($data));

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $response->getContent(),
            "Response:\n" . $response
        );
    }

    public function dataProviderTestThatPutActionReturns403ForInvalidUser(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
    }

    public function dataProviderTestThatPatchActionReturns403ForInvalidUser(): Generator
    {
        return $this->dataProviderTestThatPutActionReturns403ForInvalidUser();
    }
}
