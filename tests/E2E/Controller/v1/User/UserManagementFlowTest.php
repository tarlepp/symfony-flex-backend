<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserManagementFlowTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\Tests\E2E\TestCase\WebTestCase;
use App\Utils\JSON;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;

/**
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserManagementFlowTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `POST /v1/user` request returns `201` with expected data on response')]
    public function testThatCreateActionWorksLikeExpected(): string
    {
        $data = [
            'username' => 'test-user',
            'firstName' => 'test',
            'lastName' => 'user',
            'email' => 'test-user@test.com',
            'password' => 'some password',
            'language' => 'fi',
            'locale' => 'fi',
            'timezone' => 'Europe/Amsterdam',
        ];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('POST', $this->baseUrl, [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(201, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data['id'] = JSON::decode($content)->id;

        unset($data['password']);

        self::assertJsonStringEqualsJsonString(JSON::encode($data), $content);

        return $data['id'];
    }

    /**
     * @throws Throwable
     */
    #[Depends('testThatCreateActionWorksLikeExpected')]
    #[TestDox('Test that `PUT /v1/user/{id}` request returns `200` with expected data on response')]
    public function testThatUpdateActionWorksLikeExpected(string $id): string
    {
        $data = [
            'id' => $id,
            'username' => 'test-user',
            'firstName' => 'test-1',
            'lastName' => 'user-2',
            'email' => 'test-user@test.com',
            'language' => 'fi',
            'locale' => 'fi',
            'timezone' => 'Europe/Amsterdam',
        ];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('PUT', $this->baseUrl . '/' . $id, [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data['id'] = $id;

        self::assertJsonStringEqualsJsonString(JSON::encode($data), $content);

        return $id;
    }

    /**
     * @throws Throwable
     */
    #[Depends('testThatCreateActionWorksLikeExpected')]
    #[TestDox('Test that `PUT /v1/user/{id}` request returns `400` with partial payload')]
    public function testThatUpdateActionDoesNotWorkWithPartialData(string $id): string
    {
        $data = [
            'id' => $id,
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('PUT', $this->baseUrl . '/' . $id, [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(400, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        return $id;
    }

    /**
     * @throws Throwable
     */
    #[Depends('testThatUpdateActionWorksLikeExpected')]
    #[TestDox('Test that `PATCH /v1/user/{id}` request returns `200` and expected data when using partial payload')]
    public function testThatPatchActionWorksWithPartialData(string $id): string
    {
        $data = [
            'id' => $id,
            'email' => 'test-user2@test.com',
            'locale' => 'en',
        ];

        $expectedData = [
            'id' => $id,
            'username' => 'test-user',
            'firstName' => 'test-1',
            'lastName' => 'user-2',
            'email' => 'test-user2@test.com',
            'language' => 'fi',
            'locale' => 'en',
            'timezone' => 'Europe/Amsterdam',
        ];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('PATCH', $this->baseUrl . '/' . $id, [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertJsonStringEqualsJsonString(JSON::encode($expectedData), $content);

        return $id;
    }

    /**
     * @throws Throwable
     */
    #[Depends('testThatUpdateActionWorksLikeExpected')]
    #[TestDox('Test that `DELETE /v1/user/{id}` request returns `200`')]
    public function testThatDeleteActionWorksLikeExpected(string $id): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . $id);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "Response:\n" . $response);
    }
}
