<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/ProfileControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Controller;

use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_map;
use function str_pad;

/**
 * Class ProfileControllerTest
 *
 * @package App\Tests\E2E\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ProfileControllerTest extends WebTestCase
{
    private $baseUrl = '/profile';

    /**
     * @throws Throwable
     */
    public function testThatProfileActionReturns401WithoutToken(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($responseContent, $response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGetTokenReturnsJwtWithValidCredentials
     *
     * @param string $username
     * @param string $password
     *
     * @throws Throwable
     */
    public function testThatProfileActionReturnExpectedWithValidToken(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);

        unset($response, $client);
    }

    public function testThatProfileActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatProfileActionReturnsExpected
     *
     * @param string $token
     */
    public function testThatProfileActionReturnsExpected(string $token): void
    {
        $client = $this->getApiKeyClient($token);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($responseContent, $response, $client);
    }

    /**
     * @throws Throwable
     */
    public function testThatRolesActionReturns401WithoutToken(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($responseContent, $response, $client);
    }

    public function testThatRolesActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($responseContent, $response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatRolesActionReturnsExpected
     *
     * @param string $username
     * @param string $password
     * @param array  $expected
     *
     * @throws Throwable
     */
    public function testThatRolesActionReturnsExpected(string $username, string $password, array $expected): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame($expected, JSON::decode($response->getContent(), true), $response->getContent());

        unset($response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatRolesActionReturnsExpectedWithValidApiKey
     *
     * @param string $token
     */
    public function testThatRolesActionReturnsExpectedWithValidApiKey(string $token): void
    {
        $client = $this->getApiKeyClient($token);
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($responseContent, $response, $client);
    }

    /**
     * @throws Throwable
     */
    public function testThatGroupsActionReturns401WithoutToken(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($responseContent, $response, $client);
    }

    public function testThatGroupsActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($responseContent, $response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGroupsActionReturnExpected
     *
     * @param array  $expected
     * @param string $username
     * @param string $password
     *
     * @throws Throwable
     */
    public function testThatGroupsActionReturnExpected(string $username, string $password, array $expected): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        if (empty($expected)) {
            static::assertEmpty($responseContent);
        } else {
            $iterator = static function (stdClass $userGroup): string {
                return $userGroup->role->id;
            };

            static::assertSame($expected, array_map($iterator, $responseContent));
        }

        unset($responseContent, $response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGroupsActionReturnExpectedWithValidApiKey
     *
     * @param string $token
     */
    public function testThatGroupsActionReturnExpectedWithValidApiKey(string $token): void
    {
        $client = $this->getApiKeyClient($token);
        $client->request('GET', $this->baseUrl . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info
        );

        unset($responseContent, $response, $client);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetTokenReturnsJwtWithValidCredentials(): Generator
    {
        yield ['john',                     'password'];
        yield ['john.doe@test.com',        'password'];
        yield ['john-logged',              'password-logged'];
        yield ['john.doe-logged@test.com', 'password-logged'];
        yield ['john-user',                'password-user'];
        yield ['john.doe-user@test.com',   'password-user'];
        yield ['john-admin',               'password-admin'];
        yield ['john.doe-admin@test.com',  'password-admin'];
        yield ['john-root',                'password-root'];
        yield ['john.doe-root@test.com',   'password-root'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatProfileActionReturnsExpected(): Generator
    {
        static::bootKernel();

        $rolesService = static::$container->get(RolesService::class);

        foreach ($rolesService->getRoles() as $role) {
            yield [str_pad($rolesService->getShort($role), 40, '_')];
        }
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatRolesActionReturnsExpected(): Generator
    {
        yield ['john',                     'password',        []];
        yield ['john.doe@test.com',        'password',        []];
        yield ['john-logged',              'password-logged', ['ROLE_LOGGED']];
        yield ['john.doe-logged@test.com', 'password-logged', ['ROLE_LOGGED']];
        yield ['john-user',                'password-user',   ['ROLE_USER', 'ROLE_LOGGED']];
        yield ['john.doe-user@test.com',   'password-user',   ['ROLE_USER', 'ROLE_LOGGED']];
        yield ['john-admin',               'password-admin',  ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']];
        yield ['john.doe-admin@test.com',  'password-admin',  ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']];
        yield ['john-root',                'password-root',   ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']];
        yield ['john.doe-root@test.com',   'password-root',   ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatRolesActionReturnsExpectedWithValidApiKey(): Generator
    {
        static::bootKernel();

        $rolesService = static::$container->get(RolesService::class);

        foreach ($rolesService->getRoles() as $role) {
            yield [str_pad($rolesService->getShort($role), 40, '_')];
        }
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGroupsActionReturnExpected(): Generator
    {
        yield ['john',                     'password',        []];
        yield ['john.doe@test.com',        'password',        []];
        yield ['john-logged',              'password-logged', ['ROLE_LOGGED']];
        yield ['john.doe-logged@test.com', 'password-logged', ['ROLE_LOGGED']];
        yield ['john-user',                'password-user',   ['ROLE_USER']];
        yield ['john.doe-user@test.com',   'password-user',   ['ROLE_USER']];
        yield ['john-admin',               'password-admin',  ['ROLE_ADMIN']];
        yield ['john.doe-admin@test.com',  'password-admin',  ['ROLE_ADMIN']];
        yield ['john-root',                'password-root',   ['ROLE_ROOT']];
        yield ['john.doe-root@test.com',   'password-root',   ['ROLE_ROOT']];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGroupsActionReturnExpectedWithValidApiKey(): Generator
    {
        static::bootKernel();

        $rolesService = static::$container->get(RolesService::class);

        foreach ($rolesService->getRoles() as $role) {
            yield [str_pad($rolesService->getShort($role), 40, '_')];
        }
    }
}
