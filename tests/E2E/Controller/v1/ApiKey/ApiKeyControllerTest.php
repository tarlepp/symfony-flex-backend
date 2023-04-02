<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/ApiKey/ApiKeyControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\ApiKey;

use App\Tests\E2E\TestCase\WebTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function getenv;

/**
 * Class ApiKeyControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/api_key';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/api_key` request returns `401` for non-logged in user')]
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
    #[DataProvider('dataProviderTestThatFindActionWorksAsExpected')]
    #[TestDox('Test that `GET /v1/api_key` request returns `$e` with user `$u` + `$p`')]
    public function testThatFindActionWorksAsExpected(string $u, string $p, int $e): void
    {
        $client = $this->getTestClient($u, $p);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame($e, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: int}>
     */
    public static function dataProviderTestThatFindActionWorksAsExpected(): Generator
    {
        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john', 'password', 403];
            yield ['john-api', 'password-api', 403];
            yield ['john-logged', 'password-logged', 403];
            yield ['john-user', 'password-user', 403];
        }

        yield ['john-admin', 'password-admin', 403];
        yield ['john-root', 'password-root', 200];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password', 403];
            yield ['john.doe-api@test.com', 'password-api', 403];
            yield ['john.doe-logged@test.com', 'password-logged', 403];
            yield ['john.doe-user@test.com', 'password-user', 403];
        }

        yield ['john.doe-admin@test.com', 'password-admin', 403];
        yield ['john.doe-root@test.com', 'password-root', 200];
    }
}
