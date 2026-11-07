<?php
declare(strict_types = 1);

/**
 * /tests/E2E/Controller/v1/ApiKey/ApiKeyControllerTest.php
 */

namespace App\Tests\E2E\Controller\v1\ApiKey;

use App\Tests\E2E\TestCase\WebTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function getenv;

final class ApiKeyControllerTest extends WebTestCase
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
        self::assertResponseStatusCode(Response::HTTP_UNAUTHORIZED, $response);
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
        self::assertResponseStatusCode($e, $response);
    }

    /**
     * @return Generator<int, array{0: string, 1: string, 2: int}>
     */
    public static function dataProviderTestThatFindActionWorksAsExpected(): Generator
    {
        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john', 'password', Response::HTTP_FORBIDDEN];
            yield ['john-api', 'password-api', Response::HTTP_FORBIDDEN];
            yield ['john-logged', 'password-logged', Response::HTTP_FORBIDDEN];
            yield ['john-user', 'password-user', Response::HTTP_FORBIDDEN];
        }

        yield ['john-admin', 'password-admin', Response::HTTP_FORBIDDEN];
        yield ['john-root', 'password-root', Response::HTTP_OK];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password', Response::HTTP_FORBIDDEN];
            yield ['john.doe-api@test.com', 'password-api', Response::HTTP_FORBIDDEN];
            yield ['john.doe-logged@test.com', 'password-logged', Response::HTTP_FORBIDDEN];
            yield ['john.doe-user@test.com', 'password-user', Response::HTTP_FORBIDDEN];
        }

        yield ['john.doe-admin@test.com', 'password-admin', Response::HTTP_FORBIDDEN];
        yield ['john.doe-root@test.com', 'password-root', Response::HTTP_OK];
    }
}
