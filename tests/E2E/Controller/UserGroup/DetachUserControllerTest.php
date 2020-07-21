<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/UserGroup/DetachUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\UserGroup;

use App\DataFixtures\ORM\LoadUserData;
use App\DataFixtures\ORM\LoadUserGroupData;
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class DetachUserControllerTest
 *
 * @package App\Tests\E2E\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DetachUserControllerTest extends WebTestCase
{
    private string $baseUrl = '/user_group';

    /**
     * @throws Throwable
     */
    public static function tearDownAfterClass(): void
    {
        static::bootKernel();

        PhpUnitUtil::loadFixtures(static::$kernel);

        static::$kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `DELETE /user_group/{userGroup}/user/{user}` returns 401 for non-logged in user
     */
    public function testThatDetachUserReturns401(): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-user'];
        $userUuid = LoadUserData::$uuids['john-user'];

        $client = $this->getTestClient();
        $client->request('DELETE', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatDetachUserReturns403
     *
     * @throws Throwable
     *
     * @testdox Test that `DELETE /user_group/{userGroup}/user/{user}` returns 403 for $u + $p, who hasn't `ROLE_ROOT`
     */
    public function testThatDetachUserReturns403(string $u, string $p): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-user'];
        $userUuid = LoadUserData::$uuids['john-user'];

        $client = $this->getTestClient($u, $p);
        $client->request('DELETE', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `DELETE /user_group/{userGroup}/user/{user}` returns 200 with root user
     */
    public function testThatDetachUserReturns200(): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-user'];
        $userUuid = LoadUserData::$uuids['john-user'];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    public function dataProviderTestThatDetachUserReturns403(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
    }
}
