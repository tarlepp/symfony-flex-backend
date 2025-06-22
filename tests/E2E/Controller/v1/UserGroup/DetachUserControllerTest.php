<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/UserGroup/DetachUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\UserGroup;

use App\Tests\DataFixtures\ORM\LoadUserData;
use App\Tests\DataFixtures\ORM\LoadUserGroupData;
use App\Tests\E2E\TestCase\WebTestCase;
use App\Tests\Utils\PhpUnitUtil;
use Generator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function getenv;

/**
 * @package App\Tests\E2E\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class DetachUserControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user_group';

    /**
     * @throws Throwable
     */
    #[Override]
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        PhpUnitUtil::loadFixtures($kernel);

        $kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `DELETE /v1/user_group/{id}/user/{id}` request returns `401` for non-logged in user')]
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
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatDetachUserReturns403')]
    #[TestDox('Test that `DELETE /v1/user_group/{id}/user/{id}` request returns `403` for not root user `$u` + `$p`')]
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
     */
    #[TestDox('Test that `DELETE /v1/user_group/{id}/user/{id}` request returns `200` for root user')]
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
    public static function dataProviderTestThatDetachUserReturns403(): Generator
    {
        yield ['john', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
            yield ['john-admin', 'password-admin'];
        }

        yield ['john.doe@test.com', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
            yield ['john.doe-admin@test.com', 'password-admin'];
        }
    }
}
