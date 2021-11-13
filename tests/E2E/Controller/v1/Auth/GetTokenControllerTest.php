<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Auth/GetTokenControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Auth;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;
use function getenv;
use function json_encode;

/**
 * Class GetTokenControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Auth
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class GetTokenControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/auth/get_token';

    /**
     * @dataProvider dataProviderTestThatGetTokenRouteDoesNotAllowOtherThanPost
     *
     * @throws Throwable
     *
     * @testdox Test that `$method /v1/auth/get_token` request returns `405`
     */
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
     * @dataProvider dataProviderTestThatGetTokenReturnsJwtWithValidCredentials
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /v1/auth/get_token` request returns `200` with proper JWT with `$u` + `$p` credentials
     */
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

        // Attributes that should be present...
        $attributes = [
            'token',
        ];

        // Iterate expected attributes and check that those are present
        foreach ($attributes as $attribute) {
            $messageNotPresent = 'getToken did not return all expected attributes, missing \'' . $attribute . '\'.';
            $messageEmpty = 'Attribute \'' . $attribute . '\' is empty, this is fail...';

            self::assertObjectHasAttribute($attribute, $responseContent, $messageNotPresent);
            self::assertNotEmpty($responseContent->{$attribute}, $messageEmpty);
        }
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `POST /v1/auth/get_token` request returns `401` with invalid credentials
     */
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

        $info = "\nResponse: \n" . $response;

        self::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        self::assertSame(
            'Invalid credentials.',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatGetTokenRouteDoesNotAllowOtherThanPost(): Generator
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
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetTokenReturnsJwtWithValidCredentials(): Generator
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
