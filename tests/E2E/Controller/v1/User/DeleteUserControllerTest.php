<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/DeleteUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\Tests\DataFixtures\ORM\LoadUserData;
use App\Tests\E2E\TestCase\WebTestCase;
use App\Tests\Utils\PhpUnitUtil;
use Generator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function getenv;

/**
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class DeleteUserControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

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
    #[TestDox('Test that `DELETE /v1/user/{id}` request returns `401` for non-logged in user')]
    public function testThatDeleteUserReturns401(): void
    {
        $client = $this->getTestClient();
        $client->request('DELETE', $this->baseUrl . '/' . LoadUserData::$uuids['john']);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatDeleteUserReturns403')]
    #[TestDox('Test that `DELETE /v1/user/{id}` request returns `403` when using user `$u` + `$p`')]
    public function testThatDeleteUserReturns403(string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('DELETE', $this->baseUrl . '/' . LoadUserData::$uuids['john']);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `DELETE /v1/user/{id}` request returns `400` if user tries to remove him/herself')]
    public function testThatDeleteActionThrowsAnExceptionIfUserTriesToRemoveHimself(): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . LoadUserData::$uuids['john-root']);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(400, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertJsonStringEqualsJsonString(
            '{"message":"You cannot remove yourself...","code":0,"status":400}',
            $content,
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `DELETE /v1/user/{id}` request returns `200` for root user')]
    public function testThatDeleteActionReturns200(): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . LoadUserData::$uuids['john']);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public static function dataProviderTestThatDeleteUserReturns403(): Generator
    {
        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john', 'password'];
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
        }

        yield ['john-admin', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password'];
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
        }

        yield ['john.doe-admin@test.com', 'password-admin'];
    }
}
