<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserGroupsControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\Tests\DataFixtures\ORM\LoadUserData;
use App\Tests\E2E\TestCase\WebTestCase;
use App\Tests\Utils\StringableArrayObject;
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
final class UserGroupsControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/user/{id}/groups` request returns `401` for non-logged in user')]
    public function testThatGetUserGroupsReturnsReturns401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl . '/' . LoadUserData::$uuids['john-user'] . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetUserGroupsReturns403ForInvalidUser')]
    #[TestDox('Test that `GET /v1/user/$id/groups` request returns `403` when using user `$u` + `$p`')]
    public function testThatGetUserGroupsReturns403ForInvalidUser(string $id, string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/' . $id . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @psalm-param StringableArrayObject $e
     * @phpstan-param StringableArrayObject<int, string> $e
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetUserGroupsActionsReturns200ForUserHimself')]
    #[TestDox('Test that `GET /v1/user/$id/groups` request returns expected `$e` groups for user `$u` + `$p`')]
    public function testThatGetUserGroupsActionsReturns200ForUserHimself(
        string $id,
        string $u,
        string $p,
        StringableArrayObject $e
    ): void {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/' . $id . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data = JSON::decode($content, true);

        self::assertSame($e->getArrayCopy(), array_map(static fn (array $group): string => $group['id'], $data));
    }

    /**
     * @psalm-param StringableArrayObject $e
     * @phpstan-param StringableArrayObject<int, string> $e
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetUserGroupsReturns200ForRootRoleUser')]
    #[TestDox('Test that `GET /v1/user/$id/groups` request returns expected `$e` groups for root user')]
    public function testThatGetUserGroupsReturns200ForRootRoleUser(string $id, StringableArrayObject $e): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $id . '/groups');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data = JSON::decode($content, true);

        self::assertSame($e->getArrayCopy(), array_map(static fn (array $group): string => $group['id'], $data));
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string}>
     */
    public static function dataProviderTestThatGetUserGroupsReturns403ForInvalidUser(): Generator
    {
        yield [LoadUserData::$uuids['john-api'], 'john', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield [LoadUserData::$uuids['john-user'], 'john-logged', 'password-logged'];
            yield [LoadUserData::$uuids['john-logged'], 'john-api', 'password-api'];
            yield [LoadUserData::$uuids['john-admin'], 'john-user', 'password-user'];
            yield [LoadUserData::$uuids['john'], 'john-admin', 'password-admin'];
            yield [LoadUserData::$uuids['john-api'], 'john.doe@test.com', 'password'];
            yield [LoadUserData::$uuids['john-user'], 'john.doe-logged@test.com', 'password-logged'];
            yield [LoadUserData::$uuids['john-logged'], 'john.doe-api@test.com', 'password-api'];
            yield [LoadUserData::$uuids['john-admin'], 'john.doe-user@test.com', 'password-user'];
            yield [LoadUserData::$uuids['john'], 'john.doe-admin@test.com', 'password-admin'];
        }
    }

    /**
     * @psalm-return Generator<array{0: string, 1: string, 2: string, 3: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: string, 2: string, 3: StringableArrayObject<int, string>}>
     */
    public static function dataProviderTestThatGetUserGroupsActionsReturns200ForUserHimself(): Generator
    {
        yield [LoadUserData::$uuids['john'], 'john', 'password', new StringableArrayObject([])];
        yield [
            LoadUserData::$uuids['john-logged'],
            'john-logged',
            'password-logged',
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000001',
            ]),
        ];
        yield [
            LoadUserData::$uuids['john-api'],
            'john-api',
            'password-api',
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000002',
            ]),
        ];
        yield [
            LoadUserData::$uuids['john-user'],
            'john-user',
            'password-user',
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000003',
            ]),
        ];
        yield [
            LoadUserData::$uuids['john-admin'],
            'john-admin',
            'password-admin',
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000004',
            ]),
        ];
        yield [
            LoadUserData::$uuids['john-root'],
            'john-root',
            'password-root',
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000005',
            ]),
        ];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield [LoadUserData::$uuids['john'], 'john.doe@test.com', 'password', new StringableArrayObject([])];
            yield [
                LoadUserData::$uuids['john-logged'],
                'john.doe-logged@test.com',
                'password-logged',
                new StringableArrayObject([
                    '10000000-0000-1000-8000-000000000001',
                ]),
            ];
            yield [
                LoadUserData::$uuids['john-api'],
                'john.doe-api@test.com',
                'password-api',
                new StringableArrayObject([
                    '10000000-0000-1000-8000-000000000002',
                ]),
            ];
            yield [
                LoadUserData::$uuids['john-user'],
                'john.doe-user@test.com',
                'password-user',
                new StringableArrayObject([
                    '10000000-0000-1000-8000-000000000003',
                ]),
            ];
            yield [
                LoadUserData::$uuids['john-admin'],
                'john.doe-admin@test.com',
                'password-admin',
                new StringableArrayObject([
                    '10000000-0000-1000-8000-000000000004',
                ]),
            ];
            yield [
                LoadUserData::$uuids['john-root'],
                'john.doe-root@test.com',
                'password-root',
                new StringableArrayObject([
                    '10000000-0000-1000-8000-000000000005',
                ]),
            ];
        }
    }

    /**
     * @psalm-return Generator<array{0: string, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<int, string>}>
     */
    public static function dataProviderTestThatGetUserGroupsReturns200ForRootRoleUser(): Generator
    {
        yield [LoadUserData::$uuids['john'], new StringableArrayObject([])];
        yield [
            LoadUserData::$uuids['john-logged'],
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000001',
            ]),
        ];
        yield [
            LoadUserData::$uuids['john-api'],
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000002',
            ]),
        ];
        yield [
            LoadUserData::$uuids['john-user'],
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000003',
            ]),
        ];
        yield [
            LoadUserData::$uuids['john-admin'],
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000004',
            ]),
        ];
        yield [
            LoadUserData::$uuids['john-root'],
            new StringableArrayObject([
                '10000000-0000-1000-8000-000000000005',
            ]),
        ];
    }
}
