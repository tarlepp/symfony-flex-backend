<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/RoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Controller;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_search;
use function array_slice;

/**
 * Class RoleControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleControllerTest extends WebTestCase
{
    private string $baseUrl = '/role';

    /**
     * @throws Throwable
     */
    public function testThatGetBaseRouteReturn403(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatFindOneActionWorksAsExpected
     *
     * @param string $username
     * @param string $password
     *
     * @throws Throwable
     *
     * @testdox Test that `findOne` action returns HTTP 200 with $username + $password
     */
    public function testThatFindOneActionWorksAsExpected(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);

        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetInheritedRolesActionWorksAsExpected
     *
     * @param string $username
     * @param string $password
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

    /**
     * @return Generator
     */
    public function dataProviderTestThatFindOneActionWorksAsExpected(): Generator
    {
        yield ['john-admin',  'password-admin'];
        //yield ['john-root',   'password-root'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetInheritedRolesActionWorksAsExpected(): Generator
    {
        yield ['john',        'password'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user',   'password-user'];
        yield ['john-admin',  'password-admin'];
        yield ['john-root',   'password-root'];
    }
}
