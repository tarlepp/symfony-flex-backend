<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Role/FindOneRoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Role;

use App\Tests\E2E\TestCase\WebTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function getenv;

/**
 * Class FindOneRoleControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindOneRoleControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/role';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/role/ROLE_ADMIN` request returns `401` for non-logged in user')]
    public function testThatFindOneRoleReturns401(): void
    {
        $client = $this->getTestClient();

        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatFindOneRoleReturns403')]
    #[TestDox('Test that `GET /v1/role/ROLE_ADMIN` request returns `403` when using invalid user `$u` + `$p`')]
    public function testThatFindOneRoleReturns403(string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatFindOneActionWorksAsExpected')]
    #[TestDox('Test that `GET /v1/role/ROLE_ADMIN` request returns `200` when using valid user `$u` + `$p`')]
    public function testThatFindOneActionWorksAsExpected(string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);

        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatFindOneActionReturns404')]
    #[TestDox('Test that `GET /v1/role/ROLE_FOOBAR` request returns `404` when using valid user `$u` + `$p`')]
    public function testThatFindOneActionReturns404(string $u, string $p): void
    {
        $client = $this->getTestClient($u, $p);

        $client->request('GET', $this->baseUrl . '/ROLE_FOOBAR');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(404, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public static function dataProviderTestThatFindOneRoleReturns403(): Generator
    {
        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john', 'password'];
            yield ['john-api', 'password-api'];
            yield ['john-logged', 'password-logged'];
        }

        yield ['john-user', 'password-user'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-logged@test.com', 'password-logged'];
        }

        yield ['john.doe-user@test.com', 'password-user'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public static function dataProviderTestThatFindOneActionWorksAsExpected(): Generator
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
    public static function dataProviderTestThatFindOneActionReturns404(): Generator
    {
        return static::dataProviderTestThatFindOneActionWorksAsExpected();
    }
}
