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
     *
     * @throws \Exception
     */
    public function testThatGetTokenActionDoesNotAllowOtherThanPost(string $method): void
    {
        $client = $this->getClient();
        $client->request($method, $this->baseUrl . '/getToken');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(405, $response->getStatusCode());

        unset($response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGetTokenReturnsJwtWithValidCredentials
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatGetTokenActionReturnsJwtWithValidCredentials(string $username, string $password): void
    {
        $client = $this->getClient();
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
        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(
            200,
            $response->getStatusCode(),
            "User login was not successfully.\n" . $response
        );

        /** @noinspection NullPointerExceptionInspection */
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

        unset($responseContent, $response, $client);
    }

    public function testThatGetTokenActionReturn401WithInvalidCredentials(): void
    {
        $client = $this->getClient();
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

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('Bad credentials', $responseContent->message, 'Response message was not expected');

        unset($response, $client);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetTokenRouteDoesNotAllowOtherThanPost(): array
    {
        return [
            ['HEAD'],
            /*
            ['PUT'],
            ['DELETE'],
            ['TRACE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['PATCH'],
            */
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetTokenReturnsJwtWithValidCredentials(): array
    {
        return [
            /*
            ['john',                     'password'],
            ['john.doe@test.com',        'password'],
            ['john-logged',              'password-logged'],
            ['john.doe-logged@test.com', 'password-logged'],
            ['john-user',                'password-user'],
            ['john.doe-user@test.com',   'password-user'],
            ['john-admin',               'password-admin'],
            ['john.doe-admin@test.com',  'password-admin'],
            */
            ['john-root',                'password-root'],
            /*
            ['john.doe-root@test.com',   'password-root'],
            */
        ];
    }
}
