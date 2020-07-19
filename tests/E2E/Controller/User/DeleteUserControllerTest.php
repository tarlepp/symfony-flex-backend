<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/User/DeleteUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\User;

use App\DataFixtures\ORM\LoadUserData;
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class DeleteUserControllerTest
 *
 * @package App\Tests\E2E\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DeleteUserControllerTest extends WebTestCase
{
    private string $baseUrl = '/user';

    /**
     * @throws Throwable
     */
    public static function tearDownAfterClass(): void
    {
        static::bootKernel();

        PhpUnitUtil::loadFixtures(static::$kernel);

        static::$kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `DELETE /user/{userId}` returns 401 for non-logged in user
     */
    public function testThatDeleteUserReturns401(): void
    {
        $client = $this->getTestClient();
        $client->request('DELETE', $this->baseUrl . '/' . LoadUserData::$uuids['john']);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatDeleteUserReturns403
     *
     * @throws Throwable
     *
     * @testdox Test that `DELETE /user/{userId}` returns 403 for $username + $password, who hasn't `ROLE_ROOT` role
     */
    public function testThatDeleteUserReturns403(string $username, string $password): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('DELETE', $this->baseUrl . '/' . LoadUserData::$uuids['john']);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `DELETE /user/{userId}` returns 400 if user tries to remove himself
     */
    public function testThatDeleteActionThrowsAnExceptionIfUserTriesToRemoveHimself(): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . LoadUserData::$uuids['john-root']);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(400, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
        static::assertJsonStringEqualsJsonString(
            '{"message":"You cannot remove yourself...","code":0,"status":400}',
            $response->getContent()
        );
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `DELETE /user/{userId}` returns 200 with root user
     */
    public function testThatDeleteActionReturns200(): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', $this->baseUrl . '/' . LoadUserData::$uuids['john']);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent() . "\nResponse:\n" . $response);
    }

    public function dataProviderTestThatDeleteUserReturns403(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
    }
}
