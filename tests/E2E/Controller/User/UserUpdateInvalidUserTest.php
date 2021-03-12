<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/User/UserUpdateInvalidUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\User;

use App\DataFixtures\ORM\LoadUserData;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserUpdateInvalidUserTest
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
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $content,
            "Response:\n" . $response,
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
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $content,
            "Response:\n" . $response,
        );
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatPutActionReturns403ForInvalidUser(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatPatchActionReturns403ForInvalidUser(): Generator
    {
        return $this->dataProviderTestThatPutActionReturns403ForInvalidUser();
    }
}
