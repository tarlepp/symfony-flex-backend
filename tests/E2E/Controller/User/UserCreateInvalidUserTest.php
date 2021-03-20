<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/User/UserCreateInvalidUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\User;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserCreateInvalidUserTest
 *
 * @package App\Tests\E2E\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserCreateInvalidUserTest extends WebTestCase
{
    /**
     * @dataProvider dataProviderTestThatCreateActionReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /user` returns HTTP status code 403 with invalid user $username + $password
     */
    public function testThatCreateActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $data = [
            'username' => 'test-user',
            'firstName' => 'test',
            'lastName' => 'user',
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient($username, $password);
        $client->request('POST', '/user', [], [], [], JSON::encode($data));

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
    public function dataProviderTestThatCreateActionReturns403ForInvalidUser(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
    }
}
