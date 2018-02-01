<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/ProfileControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProfileControllerTest
 *
 * @package App\Tests\Functional\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ProfileControllerTest extends WebTestCase
{
    private $baseUrl = '/profile';

    /**
     * @throws \Exception
     */
    public function testThatProfileActionReturns401WithoutToken(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('JWT Token not found', $responseContent->message, 'Response message was not expected');

        unset($responseContent, $response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGetTokenReturnsJwtWithValidCredentials
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatProfileActionReturnExpectedWithValidToken(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        unset($response, $client);
    }

    public function testThatProfileActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('JWT Token not found', $responseContent->message, 'Response message was not expected');

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
        static::assertSame(200, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('username', $responseContent);
        static::assertObjectHasAttribute('apiKey', $responseContent);
        static::assertObjectHasAttribute('roles', $responseContent);
        static::assertSame($token, $responseContent->username);

        unset($responseContent, $response, $client);
    }

    /**
     * @throws \Exception
     */
    public function testThatRolesActionReturns401WithoutToken(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('JWT Token not found', $responseContent->message, 'Response message was not expected');

        unset($responseContent, $response, $client);
    }

    public function testThatRolesActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('JWT Token not found', $responseContent->message, 'Response message was not expected');

        unset($responseContent, $response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatRolesActionReturnsExpected
     *
     * @param string $username
     * @param string $password
     * @param array  $expected
     *
     * @throws \Exception
     */
    public function testThatRolesActionReturnsExpected(string $username, string $password, array $expected): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame($expected, JSON::decode($response->getContent(), true), $response->getContent());

        unset($response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatRolesActionReturnsExpectedWithValidApiKey
     *
     * @param string $token
     * @param array  $expected
     */
    public function testThatRolesActionReturnsExpectedWithValidApiKey(string $token, array $expected): void
    {
        $client = $this->getApiKeyClient($token);
        $client->request('GET', $this->baseUrl . '/roles');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        $actual = JSON::decode($response->getContent(), true);

        \sort($expected);
        \sort($actual);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame($expected, $actual, $response->getContent());

        unset($actual, $response, $client);
    }

    /**
     * @throws \Exception
     */
    public function testThatGroupsActionReturns401WithoutToken(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('JWT Token not found', $responseContent->message, 'Response message was not expected');

        unset($responseContent, $response, $client);
    }

    public function testThatGroupsActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain \'code\'');
        static::assertSame(401, $responseContent->code, 'Response code was not expected');

        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain \'message\'');
        static::assertSame('JWT Token not found', $responseContent->message, 'Response message was not expected');

        unset($responseContent, $response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGroupsActionReturnExpected
     *
     * @param array  $expected
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function testThatGroupsActionReturnExpected(string $username, string $password, array $expected): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        if (empty($expected)) {
            static::assertEmpty($responseContent);
        } else {
            $iterator = function (\stdClass $userGroup): string {
                return $userGroup->role->id;
            };

            static::assertSame($expected, \array_map($iterator, $responseContent));
        }

        unset($responseContent, $response, $client);
    }

    /**
     * @dataProvider dataProviderTestThatGroupsActionReturnExpectedWithValidApiKey
     *
     * @param string $token
     * @param array  $expected
     */
    public function testThatGroupsActionReturnExpectedWithValidApiKey(string $token, array $expected): void
    {
        $client = $this->getApiKeyClient($token);
        $client->request('GET', $this->baseUrl . '/groups');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        $responseContent = JSON::decode($response->getContent());

        if (empty($expected)) {
            static::assertEmpty($responseContent);
        } else {
            $iterator = function (\stdClass $userGroup): string {
                return $userGroup->role->id;
            };

            static::assertSame($expected, \array_map($iterator, $responseContent));
        }

        unset($responseContent, $response, $client);
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
    public function dataProviderTestThatProfileActionReturnsExpected(): array
    {
        self::bootKernel();

        $rolesService = static::$kernel->getContainer()->get('test.service_locator')->get(RolesService::class);

        $iterator = function (string $role) use ($rolesService): array {
            return [\str_pad($rolesService->getShort($role), 40, '_')];
        };

        return \array_map($iterator, $rolesService->getRoles());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRolesActionReturnsExpected(): array
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

    /**
     * @return array
     */
    public function dataProviderTestThatRolesActionReturnsExpectedWithValidApiKey(): array
    {
        self::bootKernel();

        $rolesService = static::$kernel->getContainer()->get('test.service_locator')->get(RolesService::class);

        $iterator = function (string $role) use ($rolesService): array {
            return [
                \str_pad($rolesService->getShort($role), 40, '_'),
                \array_unique(\array_merge([RolesService::ROLE_API], $rolesService->getInheritedRoles([$role]))),
            ];
        };

        return \array_map($iterator, $rolesService->getRoles());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGroupsActionReturnExpected(): array
    {
        return [
            ['john',                     'password',        []],
            ['john.doe@test.com',        'password',        []],
            ['john-logged',              'password-logged', ['ROLE_LOGGED']],
            ['john.doe-logged@test.com', 'password-logged', ['ROLE_LOGGED']],
            ['john-user',                'password-user',   ['ROLE_USER']],
            ['john.doe-user@test.com',   'password-user',   ['ROLE_USER']],
            ['john-admin',               'password-admin',  ['ROLE_ADMIN']],
            ['john.doe-admin@test.com',  'password-admin',  ['ROLE_ADMIN']],
            ['john-root',                'password-root',   ['ROLE_ROOT']],
            ['john.doe-root@test.com',   'password-root',   ['ROLE_ROOT']],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGroupsActionReturnExpectedWithValidApiKey(): array
    {
        self::bootKernel();

        $rolesService = static::$kernel->getContainer()->get('test.service_locator')->get(RolesService::class);

        $iterator = function (string $role) use ($rolesService): array {
            return [
                \str_pad($rolesService->getShort($role), 40, '_'),
                [$role],
            ];
        };

        return \array_map($iterator, $rolesService->getRoles());
    }
}
