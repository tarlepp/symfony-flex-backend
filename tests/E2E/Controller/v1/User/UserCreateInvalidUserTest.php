<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserCreateInvalidUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\Tests\E2E\TestCase\WebTestCase;
use App\Utils\JSON;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function getenv;

/**
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserCreateInvalidUserTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `POST /v1/user` request returns `401` for non-logged in user')]
    public function testThatGetUserGroupsReturnsReturns401(): void
    {
        $data = [
            'username' => 'test-user',
            'firstName' => 'test',
            'lastName' => 'user',
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient();
        $client->request('POST', '/v1/user', [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatCreateActionReturns403ForInvalidUser')]
    #[TestDox('Test that `POST /v1/user` request returns code `403` when using invalid user `$u` + `$p`')]
    public function testThatCreateActionReturns403ForInvalidUser(string $u, string $p): void
    {
        $data = [
            'username' => 'test-user',
            'firstName' => 'test',
            'lastName' => 'user',
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient($u, $p);
        $client->request('POST', '/v1/user', [], [], [], JSON::encode($data));

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
    public static function dataProviderTestThatCreateActionReturns403ForInvalidUser(): Generator
    {
        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john', 'password'];
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
        }

        yield ['john-admin', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password'];
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
        }

        yield ['john.doe-admin@test.com', 'password-admin'];
    }
}
