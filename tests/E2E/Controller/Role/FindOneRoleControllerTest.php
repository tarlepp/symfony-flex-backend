<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Role/FindOneRoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\Role;

use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class FindOneRoleControllerTest
 *
 * @package App\Tests\E2E\Controller\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindOneRoleControllerTest extends WebTestCase
{
    private string $baseUrl = '/role';

    /**
     * @dataProvider dataProviderTestThatFindOneActionWorksAsExpected
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

    public function dataProviderTestThatFindOneActionWorksAsExpected(): Generator
    {
        yield ['john-admin', 'password-admin'];
        //yield ['john-root',   'password-root'];
    }
}
