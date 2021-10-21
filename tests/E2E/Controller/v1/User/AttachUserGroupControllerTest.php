<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/AttachUserGroupControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\DataFixtures\ORM\LoadUserData;
use App\DataFixtures\ORM\LoadUserGroupData;
use App\Utils\JSON;
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class AttachUserGroupControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AttachUserGroupControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

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
     * @testdox Test that `POST /v1/user/{id}/group/{id}` returns HTTP status `401` for non-logged in user
     */
    public function testThatAttachUserGroupReturns401(): void
    {
        $userUuid = LoadUserData::$uuids['john'];
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];

        $client = $this->getTestClient();
        $client->request('POST', $this->baseUrl . '/' . $userUuid . '/group/' . $groupUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserGroupReturns403
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /v1/user/{id}/group/{id}` returns `403` when using `$u` + `$p`, who hasn't `ROLE_ROOT`
     */
    public function testThatAttachUserGroupReturns403(string $u, string $p): void
    {
        $userUuid = LoadUserData::$uuids['john'];
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];

        $client = $this->getTestClient($u, $p);
        $client->request('POST', $this->baseUrl . '/' . $userUuid . '/group/' . $groupUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserGroupWorksAsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /v1/user/{id}/group/{id}` returns HTTP status `$expectedStatus` when using root user
     */
    public function testThatAttachUserGroupWorksAsExpected(int $expectedStatus): void
    {
        $userUuid = LoadUserData::$uuids['john'];
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('POST', $this->baseUrl . '/' . $userUuid . '/group/' . $groupUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame($expectedStatus, $response->getStatusCode(), "Response:\n" . $response);
        self::assertCount(1, JSON::decode($content));
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatAttachUserGroupReturns403(): Generator
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

    /**
     * @return Generator<array{0: int}>
     */
    public function dataProviderTestThatAttachUserGroupWorksAsExpected(): Generator
    {
        yield [201];
        yield [200];
    }
}
