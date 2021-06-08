<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Profile/RolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\Profile;

use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use JsonException;
use Throwable;
use function str_pad;

/**
 * Class RolesControllerTest
 *
 * @package App\Tests\E2E\Controller\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RolesControllerTest extends WebTestCase
{
    private string $baseUrl = '/profile/roles';

    /**
     * @throws Throwable
     */
    public function testThatRolesActionReturns401WithoutToken(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @throws JsonException
     */
    public function testThatRolesActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @dataProvider dataProviderTestThatRolesActionReturnsExpected
     *
     * @param array<int, string> $expected
     *
     * @throws Throwable
     *
     * @testdox Test that `roles` action returns expected roles with $username + $password
     */
    public function testThatRolesActionReturnsExpected(string $username, string $password, array $expected): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        static::assertSame($expected, JSON::decode($content, true), $content);
    }

    /**
     * @dataProvider dataProviderTestThatRolesActionReturnsExpectedWithValidApiKey
     *
     * @throws JsonException
     *
     * @testdox Test that `roles` action returns expected with invalid $token token.
     */
    public function testThatRolesActionReturnsExpectedWithValidApiKey(string $token): void
    {
        $client = $this->getApiKeyClient($token);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        static::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        static::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        static::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        static::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: array<int, string>}>
     */
    public function dataProviderTestThatRolesActionReturnsExpected(): Generator
    {
        yield ['john', 'password', []];
        yield ['john-logged', 'password-logged', ['ROLE_LOGGED']];
        yield ['john-user', 'password-user', ['ROLE_USER', 'ROLE_LOGGED']];
        yield ['john-admin', 'password-admin', ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']];
        yield ['john-root', 'password-root', ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']];
        yield ['john.doe@test.com', 'password', []];
        yield ['john.doe-logged@test.com', 'password-logged', ['ROLE_LOGGED']];
        yield ['john.doe-user@test.com', 'password-user', ['ROLE_USER', 'ROLE_LOGGED']];
        yield ['john.doe-admin@test.com', 'password-admin', ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']];
        yield ['john.doe-root@test.com', 'password-root', ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED']];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatRolesActionReturnsExpectedWithValidApiKey(): Generator
    {
        $rolesService = static::getContainer()->get(RolesService::class);

        foreach ($rolesService->getRoles() as $role) {
            yield [str_pad($rolesService->getShort($role), 40, '_')];
        }
    }
}
