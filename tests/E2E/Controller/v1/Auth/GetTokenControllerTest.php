<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Auth/GetTokenControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Auth;

use App\Tests\E2E\TestCase\WebTestCase;
use App\Utils\JSON;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function getenv;
use function json_encode;
use function property_exists;

/**
 * @package App\Tests\E2E\Controller\v1\Auth
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class GetTokenControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/auth/get_token';

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetTokenRouteDoesNotAllowOtherThanPost')]
    #[TestDox('Test that `$method /v1/auth/get_token` request returns `405`')]
    public function testThatGetTokenActionDoesNotAllowOtherThanPost(string $method): void
    {
        $client = $this->getTestClient();
        $client->request($method, $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(405, $response->getStatusCode(), $content);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetTokenReturnsJwtWithValidCredentials')]
    #[TestDox('Test that `POST /v1/auth/get_token` request returns `200` with proper JWT with `$u` + `$p` credentials')]
    public function testThatGetTokenActionReturnsJwtWithValidCredentials(string $u, string $p): void
    {
        $payload = json_encode(
            [
                'username' => $u,
                'password' => $p,
            ],
            JSON_THROW_ON_ERROR,
        );

        $client = $this->getTestClient();
        $client->request(
            'POST',
            $this->baseUrl,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ],
            $payload
        );

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(
            200,
            $response->getStatusCode(),
            "User login was not successfully with payload:\n" . $payload . "\nResponse: \n" . $response
        );

        $responseContent = JSON::decode($content);

        self::assertIsObject($responseContent);

        // Attributes that should be present...
        $attributes = [
            'token',
        ];

        // Iterate expected attributes and check that those are present
        foreach ($attributes as $attribute) {
            $messageNotPresent = 'getToken did not return all expected attributes, missing \'' . $attribute . '\'.';
            $messageEmpty = 'Attribute \'' . $attribute . '\' is empty, this is fail...';

            self::assertTrue(property_exists($responseContent, $attribute), $messageNotPresent);
            self::assertNotEmpty($responseContent->{$attribute}, $messageEmpty);
        }
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `POST /v1/auth/get_token` request returns `401` with invalid credentials')]
    public function testThatGetTokenActionReturn401WithInvalidCredentials(): void
    {
        $client = $this->getTestClient();
        $client->request(
            'POST',
            $this->baseUrl,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ],
            json_encode([
                'username' => 'username',
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode());

        $responseContent = JSON::decode($content);

        self::assertIsObject($responseContent);

        $info = "\nResponse: \n" . $response;

        self::assertTrue(property_exists($responseContent, 'code'), 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertTrue(property_exists($responseContent, 'message'), 'Response does not contain "message"' . $info);
        self::assertSame(
            'Invalid credentials.',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @return Generator<array-key, array{0: string}>
     */
    public static function dataProviderTestThatGetTokenRouteDoesNotAllowOtherThanPost(): Generator
    {
        yield ['HEAD'];
        yield ['PUT'];
        yield ['DELETE'];
        yield ['TRACE'];
        yield ['OPTIONS'];
        yield ['CONNECT'];
        yield ['PATCH'];
    }

    /**
     * @return Generator<array-key, array{0: string, 1: string}>
     */
    public static function dataProviderTestThatGetTokenReturnsJwtWithValidCredentials(): Generator
    {
        yield ['john', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
            yield ['john-admin', 'password-admin'];
            yield ['john-root', 'password-root'];
        }

        yield ['john.doe@test.com', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
            yield ['john.doe-admin@test.com', 'password-admin'];
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }
}
