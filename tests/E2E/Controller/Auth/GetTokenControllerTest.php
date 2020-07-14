<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Auth/GetTokenControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\Auth;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function json_encode;

/**
 * Class GetTokenControllerTest
 *
 * @package App\Tests\E2E\Controller\Auth
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class GetTokenControllerTest extends WebTestCase
{
    private string $baseUrl = '/auth/getToken';

    /**
     * @dataProvider dataProviderTestThatGetTokenRouteDoesNotAllowOtherThanPost
     *
     * @throws Throwable
     *
     * @testdox Test that `getToken` action returns 405 with $method method
     */
    public function testThatGetTokenActionDoesNotAllowOtherThanPost(string $method): void
    {
        $client = $this->getTestClient();
        $client->request($method, $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatGetTokenReturnsJwtWithValidCredentials
     *
     * @throws Throwable
     *
     * @testdox Test that `getToken` action returns proper JWT with $username + $password
     */
    public function testThatGetTokenActionReturnsJwtWithValidCredentials(string $username, string $password): void
    {
        $payload = json_encode(compact('username', 'password'), JSON_THROW_ON_ERROR);

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

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(
            200,
            $response->getStatusCode(),
            "User login was not successfully with payload:\n" . $payload . "\nResponse: \n" . $response
        );

        $responseContent = JSON::decode($response->getContent());

        // Attributes that should be present...
        $attributes = [
            'token',
        ];

        // Iterate expected attributes and check that those are present
        foreach ($attributes as $attribute) {
            $messageNotPresent = 'getToken did not return all expected attributes, missing \'' . $attribute . '\'.';
            $messageEmpty = 'Attribute \'' . $attribute . '\' is empty, this is fail...';

            static::assertObjectHasAttribute($attribute, $responseContent, $messageNotPresent);
            static::assertNotEmpty($responseContent->{$attribute}, $messageEmpty);
        }
    }

    /**
     * @throws Throwable
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
            json_encode(['username' => 'username', 'password' => 'password'], JSON_THROW_ON_ERROR)
        );

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());

        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse: \n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'Invalid credentials.',
            $responseContent->message,
            'Response message was not expected' . $info
        );
    }

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

    public function dataProviderTestThatGetTokenReturnsJwtWithValidCredentials(): Generator
    {
        yield ['john', 'password'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
        yield ['john-root', 'password-root'];
        yield ['john.doe@test.com', 'password'];
        yield ['john.doe-logged@test.com', 'password-logged'];
        yield ['john.doe-user@test.com', 'password-user'];
        yield ['john.doe-admin@test.com', 'password-admin'];
        yield ['john.doe-root@test.com', 'password-root'];
    }
}
