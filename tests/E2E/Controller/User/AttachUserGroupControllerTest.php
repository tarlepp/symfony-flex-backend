<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/User/AttachUserGroupControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\User;

use App\DataFixtures\ORM\LoadUserData;
use App\DataFixtures\ORM\LoadUserGroupData;
use App\Utils\JSON;
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class AttachUserGroupControllerTest
 *
 * @package App\Tests\E2E\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AttachUserGroupControllerTest extends WebTestCase
{
    private string $baseUrl = '/user';

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
     * @testdox Test that `POST /user/{userId}/group/{userGroupId}` returns 401 for non-logged in user
     */
    public function testThatAttachUserGroupReturns401(): void
    {
        $userUuid = LoadUserData::$uuids['john'];
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];

        $client = $this->getTestClient();
        $client->request('POST', $this->baseUrl . '/' . $userUuid . '/group/' . $groupUuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserGroupReturns403
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /user/{userId}/group/{userGroupId}` returns 403 for $u + $p, who hasn't `ROLE_ROOT` role
     */
    public function testThatAttachUserGroupReturns403(string $u, string $p): void
    {
        $userUuid = LoadUserData::$uuids['john'];
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];

        $client = $this->getTestClient($u, $p);
        $client->request('POST', $this->baseUrl . '/' . $userUuid . '/group/' . $groupUuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserGroupWorksAsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /user/{userId}/group/{userGroupId}` returns status $expectedStatus with root user
     */
    public function testThatAttachUserGroupWorksAsExpected(int $expectedStatus): void
    {
        $userUuid = LoadUserData::$uuids['john'];
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('POST', $this->baseUrl . '/' . $userUuid . '/group/' . $groupUuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame($expectedStatus, $response->getStatusCode(), "Response:\n" . $response);
        static::assertCount(1, JSON::decode($response->getContent()));
    }

    public function dataProviderTestThatAttachUserGroupReturns403(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
    }

    public function dataProviderTestThatAttachUserGroupWorksAsExpected(): Generator
    {
        yield [201];
        yield [200];
    }
}
