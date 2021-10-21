<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/UserGroup/DetachUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\UserGroup;

use App\DataFixtures\ORM\LoadUserData;
use App\DataFixtures\ORM\LoadUserGroupData;
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class DetachUserControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DetachUserControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user_group';

    /**
     * @throws Throwable
     */
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();

        PhpUnitUtil::loadFixtures(self::$kernel);

        self::$kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `DELETE /v1/user_group/{id}/user/{id}` returns HTTP status 401 for non-logged in user
     */
    public function testThatDetachUserReturns401(): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-user'];
        $userUuid = LoadUserData::$uuids['john-user'];

        $client = $this->getTestClient();
        $client->request('DELETE', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatDetachUserReturns403
     *
     * @throws Throwable
     *
     * @testdox Test that `DELETE /v1/user_group/{id}/user/{id}` returns `403` for `$u` + `$p`, who hasn't `ROLE_ROOT`
     */
    public function testThatDetachUserReturns403(string $u, string $p): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-user'];
        $userUuid = LoadUserData::$uuids['john-user'];

        $client = $this->getTestClient($u, $p);
        $client->request('DELETE', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `DELETE /v1/user_group/{id}/user/{id}` returns HTTP status `200` for user who has `ROLE_ROOT`
     */
    public function testThatDetachUserReturns200(): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-user'];
        $userUuid = LoadUserData::$uuids['john-user'];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatDetachUserReturns403(): Generator
    {
        yield ['john', 'password'];
        yield ['john-logged', 'password-logged'];
        yield ['john-api', 'password-api'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
        yield ['john.doe@test.com', 'password'];
        yield ['john.doe-logged@test.com', 'password-logged'];
        yield ['john.doe-api@test.com', 'password-api'];
        yield ['john.doe-user@test.com', 'password-user'];
        yield ['john.doe-admin@test.com', 'password-admin'];
    }
}
