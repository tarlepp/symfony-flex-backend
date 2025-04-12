<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/UserGroup/UsersControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\UserGroup;

use App\Tests\DataFixtures\ORM\LoadUserGroupData;
use App\Tests\E2E\TestCase\WebTestCase;
use App\Utils\JSON;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;

/**
 * @package App\Tests\E2E\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UsersControllerTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetUserGroupUsersActionReturnsExpected')]
    #[TestDox('Test that `GET /v1/user_group/$id/users` request returns `200` and expected count `$c` of users')]
    public function testThatGetUserGroupUsersActionReturnsExpected(int $c, string $id): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', '/v1/user_group/' . $id . '/users');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertCount($c, JSON::decode($content));
    }

    /**
     * @return Generator<array{0: int, 1: string}>
     */
    public static function dataProviderTestThatGetUserGroupUsersActionReturnsExpected(): Generator
    {
        yield [1, LoadUserGroupData::$uuids['Role-root']];
        yield [2, LoadUserGroupData::$uuids['Role-admin']];
        yield [3, LoadUserGroupData::$uuids['Role-user']];
        yield [1, LoadUserGroupData::$uuids['Role-api']];
        yield [5, LoadUserGroupData::$uuids['Role-logged']];
    }
}
