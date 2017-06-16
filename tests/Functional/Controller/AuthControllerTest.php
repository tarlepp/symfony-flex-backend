<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/AuthControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Utils\JSON;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
    public function testThatGetTokenRouteDoesNotAllowOtherThanPost(string $method): void
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
    public function testThatGetTokenReturnsJwtWithValidCredentials(string $username, string $password): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/auth/getToken',
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

    public function testThatGetTokenRouteReturn401WithInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/auth/getToken',
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
    
    public function testThatGetProfileReturns401WithoutToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/auth/profile');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());

        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(403, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('status', $responseContent, 'Response does not contain \'status\'');
        static::assertSame(401, $responseContent->status, 'Response status was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('Access denied.', $responseContent->message, 'Response message was not expected');
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
            ['john',                        'password'],
            ['john.doe@test.com',           'password'],
            ['john-logged',                 'password-logged'],
            ['john.doe-logged@test.com',    'password-logged'],
            ['john-user',                   'password-user'],
            ['john.doe-user@test.com',      'password-user'],
            ['john-admin',                  'password-admin'],
            ['john.doe-admin@test.com',     'password-admin'],
            ['john-root',                   'password-root'],
            ['john.doe-root@test.com',      'password-root'],
        ];
    }
}
