<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/User/UserManagementFlowTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\User;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Throwable;

/**
 * Class UserManagementFlowTest
 *
 * @package App\Tests\E2E\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserManagementFlowTest extends WebTestCase
{
    private string $baseUrl = '/user';

    /**
     * @throws Throwable
     *
     * @testdox Test that `POST /user` with proper payload creates new user
     */
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

        static::assertNotFalse($content);
        static::assertSame(201, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data['id'] = JSON::decode($content)->id;

        unset($data['password']);

        static::assertJsonStringEqualsJsonString(JSON::encode($data), $content);

        return $data['id'];
    }

    /**
     * @depends testThatCreateActionWorksLikeExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `PUT /user/{userId}` returns expected
     */
    public function testThatUpdateActionWorksLikeExpected(string $userId): string
    {
        $data = [
            'id' => $userId,
            'username' => 'test-user',
            'firstName' => 'test-1',
            'lastName' => 'user-2',
            'email' => 'test-user@test.com',
            'language' => 'fi',
            'locale' => 'fi',
            'timezone' => 'Europe/Amsterdam',
        ];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('PUT', $this->baseUrl . '/' . $userId, [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        $data['id'] = $userId;

        static::assertJsonStringEqualsJsonString(JSON::encode($data), $content);

        return $userId;
    }

    /**
     * @depends testThatCreateActionWorksLikeExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `PUT /user/{userId}` returns HTTP status 400 with partial data
     */
    public function testThatUpdateActionDoesNotWorkWithPartialData(string $userId): string
    {
        $data = [
            'id' => $userId,
            'email' => 'test-user@test.com',
        ];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('PUT', $this->baseUrl . '/' . $userId, [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(400, $response->getStatusCode(), $content . "\nResponse:\n" . $response);

        return $userId;
    }

    /**
     * @depends testThatUpdateActionWorksLikeExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `PATCH /user/{userId}` returns expected data
     */
    public function testThatPatchActionWorksWithPartialData(string $userId): string
    {
        $data = [
            'id' => $userId,
            'email' => 'test-user2@test.com',
            'locale' => 'en',
        ];

        $expectedData = [
            'id' => $userId,
            'username' => 'test-user',
            'firstName' => 'test-1',
            'lastName' => 'user-2',
            'email' => 'test-user2@test.com',
            'language' => 'fi',
            'locale' => 'en',
            'timezone' => 'Europe/Amsterdam',
        ];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('PATCH', $this->baseUrl . '/' . $userId, [], [], [], JSON::encode($data));

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        static::assertJsonStringEqualsJsonString(JSON::encode($expectedData), $content);

        return $userId;
    }

    /**
     * @depends testThatUpdateActionWorksLikeExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `DELETE /user/{userId}` returns HTTP 200 with valid user
     */
    public function testThatDeleteActionWorksLikeExpected(string $userId): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . $userId);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(200, $response->getStatusCode(), $content . "Response:\n" . $response);
    }
}
