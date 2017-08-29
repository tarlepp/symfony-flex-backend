<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/AuthControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Utils\Tests\WebTestCase;
use App\Utils\JSON;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthControllerTest
 *
 * @package App\Tests\Functional\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthControllerTest extends WebTestCase
{
    private $baseUrl = '/auth';

    /**
     * @dataProvider dataProviderTestThatGetTokenRouteDoesNotAllowOtherThanPost
     *
     * @param string $method
     */
    public function testThatGetTokenActionDoesNotAllowOtherThanPost(string $method): void
    {
        $client = static::createClient();
        $client->request($method, $this->baseUrl . '/getToken');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatGetTokenReturnsJwtWithValidCredentials
     *
     * @param string $username
     * @param string $password
     */
    public function testThatGetTokenActionReturnsJwtWithValidCredentials(string $username, string $password): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            $this->baseUrl . '/getToken',
            [],
            [],
            [
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ],
            \json_encode(['username' => $username, 'password' => $password])
        );

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        // Check that HTTP status code is correct
        static::assertSame(
            200,
            $response->getStatusCode(),
            "User login was not successfully.\n" . $response
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

    public function testThatGetTokenActionReturn401WithInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            $this->baseUrl . '/getToken',
            [],
            [],
            [
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ],
            \json_encode(['username' => 'username', 'password' => 'password'])
        );

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());

        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('Bad credentials', $responseContent->message, 'Response message was not expected');
    }
    
    public function testThatProfileActionReturns401WithoutToken(): void
    {
        $client = static::createClient();
        $client->request('GET', $this->baseUrl . '/profile');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());

        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('JWT Token not found', $responseContent->message, 'Response message was not expected');
    }

    /**
     * @dataProvider dataProviderTestThatGetTokenReturnsJwtWithValidCredentials
     *
     * @param string $username
     * @param string $password
     */
    public function testThatGetProfileActionReturnExpectedWithValidToken(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/profile');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent());
    }

    public function testThatGetRolesActionReturns401WithoutToken(): void
    {
        $client = static::createClient();
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());

        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('JWT Token not found', $responseContent->message, 'Response message was not expected');
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesActionReturnsExpected
     *
     * @param string $username
     * @param string $password
     * @param array  $expected
     */
    public function testThatGetRolesActionReturnsExpected(string $username, string $password, array $expected): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent());
        static::assertSame($expected, JSON::decode($response->getContent(), true), $response->getContent());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetTokenRouteDoesNotAllowOtherThanPost(): array
    {
        return [
            ['HEAD'],
            ['PUT'],
            ['DELETE'],
            ['TRACE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['PATCH'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetTokenReturnsJwtWithValidCredentials(): array
    {
        return [
            ['john',                     'password'],
            ['john.doe@test.com',        'password'],
            ['john-logged',              'password-logged'],
            ['john.doe-logged@test.com', 'password-logged'],
            ['john-user',                'password-user'],
            ['john.doe-user@test.com',   'password-user'],
            ['john-admin',               'password-admin'],
            ['john.doe-admin@test.com',  'password-admin'],
            ['john-root',                'password-root'],
            ['john.doe-root@test.com',   'password-root'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetRolesActionReturnsExpected(): array
    {
        return [
            ['john',                     'password',        []],
            ['john.doe@test.com',        'password',        []],
            ['john-logged',              'password-logged', ['ROLE_LOGGED']],
            ['john.doe-logged@test.com', 'password-logged', ['ROLE_LOGGED']],
            ['john-user',                'password-user',   ['ROLE_USER', 'ROLE_LOGGED']],
            ['john.doe-user@test.com',   'password-user',   ['ROLE_USER', 'ROLE_LOGGED']],
            ['john-admin',               'password-admin',  ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']],
            ['john.doe-admin@test.com',  'password-admin',  ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']],
            ['john-root',                'password-root',   ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']],
            ['john.doe-root@test.com',   'password-root',   ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']],
        ];
    }
}
