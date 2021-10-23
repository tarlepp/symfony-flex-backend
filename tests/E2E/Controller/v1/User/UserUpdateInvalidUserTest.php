<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserUpdateInvalidUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\DataFixtures\ORM\LoadUserData;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserUpdateInvalidUserTest
 *
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserUpdateInvalidUserTest extends WebTestCase
{
    /**
     * @dataProvider dataProviderTestThatPutActionReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `PUT /v1/user/{id}` request returns `403` when using invalid user `$u` + `$p`
     */
    public function testThatPutActionReturns403ForInvalidUser(string $u, string $p): void
    {
        $data = [
            'username' => 'test-user',
            'firstName' => 'test-1',
            'lastName' => 'user-2',
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient($u, $p);
        $client->request('PUT', '/v1/user/' . LoadUserData::$uuids['john'], [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString(
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
     * @testdox Test that `PATCH /v1/user/{id}` request returns `403` when using invalid user `$u` + `$p`
     */
    public function testThatPatchActionReturns403ForInvalidUser(string $u, string $p): void
    {
        $data = [
            'username' => 'test-user',
            'firstName' => 'test-1',
            'lastName' => 'user-2',
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient($u, $p);
        $client->request('PUT', '/v1/user/' . LoadUserData::$uuids['john'], [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString(
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

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
            yield ['john-admin', 'password-admin'];
        }

        yield ['john.doe@test.com', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
            yield ['john.doe-admin@test.com', 'password-admin'];
        }
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatPatchActionReturns403ForInvalidUser(): Generator
    {
        return $this->dataProviderTestThatPutActionReturns403ForInvalidUser();
    }
}
