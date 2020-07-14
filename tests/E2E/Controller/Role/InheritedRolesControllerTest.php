<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Role/InheritedRolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\Role;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_search;
use function array_slice;

/**
 * Class InheritedRolesControllerTest
 *
 * @package App\Tests\E2E\Controller\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class InheritedRolesControllerTest extends WebTestCase
{
    private string $baseUrl = '/role';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /role/ROLE_ADMIN/inherited` returns HTTP 401 for non-logged in user.
     */
    public function testThatGetInheritedRoles401(): void
    {
        $client = $this->getTestClient();

        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN/inherited');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetInheritedRolesActionWorksAsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that inherited roles are expected with $username + $password
     */
    public function testThatGetInheritedRolesActionWorksAsExpected(string $username, string $password): void
    {
        $roles = ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED'];

        $client = $this->getTestClient($username, $password);

        foreach ($roles as $role) {
            $offset = array_search($role, $roles, true);
            $expectedRoles = array_slice($roles, $offset);

            $client->request('GET', $this->baseUrl . '/' . $role . '/inherited');

            $response = $client->getResponse();

            static::assertInstanceOf(Response::class, $response);
            static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
            static::assertJsonStringEqualsJsonString(JSON::encode($expectedRoles), $response->getContent());
        }
    }

    public function dataProviderTestThatGetInheritedRolesActionWorksAsExpected(): Generator
    {
        yield ['john', 'password'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
        yield ['john-root', 'password-root'];
    }
}
