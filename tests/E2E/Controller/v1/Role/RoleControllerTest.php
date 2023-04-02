<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Role/RoleControllerTest.php
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
 * Class RoleControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/role';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/role` request returns `401` for non-logged in user')]
    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetBaseRouteReturn403')]
    #[TestDox('Test that `GET /v1/role` request returns `403` when using invalid user `$username` + `$password`')]
    public function testThatGetBaseRouteReturn403(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetBaseRouteReturn200')]
    #[TestDox('Test that `GET /v1/role` request returns `200` when using valid user `$username` + `$password`')]
    public function testThatGetBaseRouteReturn200(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public static function dataProviderTestThatGetBaseRouteReturn403(): Generator
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
    public static function dataProviderTestThatGetBaseRouteReturn200(): Generator
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
}
