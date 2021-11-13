<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Role/InheritedRolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Role;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;
use function array_search;
use function array_slice;
use function getenv;

/**
 * Class InheritedRolesControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class InheritedRolesControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/role';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/role/ROLE_ADMIN/inherited` request returns `401` for non-logged in user
     */
    public function testThatGetInheritedRoles401(): void
    {
        $client = $this->getTestClient();

        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN/inherited');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetInheritedRoles403
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/role/ROLE_ADMIN/inherited` request returns `403` when using invalid user `$u` + `$p`
     */
    public function testThatGetInheritedRoles403(string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN/inherited');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetInheritedRoles200
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/role/ROLE_ADMIN/inherited` request returns `200` when using valid user `$u` + `$p`
     */
    public function testThatGetInheritedRoles200(string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN/inherited');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetInheritedRolesActionWorksAsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/role/{role}/inherited` request returns expected roles for valid user `$u` + `$p`
     */
    public function testThatGetInheritedRolesActionWorksAsExpected(string $u, string $p): void
    {
        $roles = ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED'];

        $client = $this->getTestClient($u, $p);

        foreach ($roles as $role) {
            $offset = array_search($role, $roles, true);

            self::assertIsInt($offset);

            $expectedRoles = array_slice($roles, $offset);

            $client->request('GET', $this->baseUrl . '/' . $role . '/inherited');

            $response = $client->getResponse();
            $content = $response->getContent();

            self::assertNotFalse($content);
            self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
            self::assertJsonStringEqualsJsonString(JSON::encode($expectedRoles), $content);
        }
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetInheritedRoles403(): Generator
    {
        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john', 'password'];
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
        }

        yield ['john-user', 'password-user'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password'];
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
        }

        yield ['john.doe-user@test.com', 'password-user'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetInheritedRoles200(): Generator
    {
        yield ['john-admin', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-root', 'password-root'];
        }

        yield ['john.doe-admin@test.com', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetInheritedRolesActionWorksAsExpected(): Generator
    {
        yield ['john-admin', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-root', 'password-root'];
        }

        yield ['john.doe-admin@test.com', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }
}
