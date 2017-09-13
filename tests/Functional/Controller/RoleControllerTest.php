<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/RoleControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RoleControllerTest
 *
 * @package App\Tests\Functional\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleControllerTest extends WebTestCase
{
    private $baseUrl = '/role';

    public function testThatGetBaseRouteReturn403(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatFindOneActionWorksAsExpected
     *
     * @param string $username
     * @param string $password
     */
    public function testThatFindOneActionWorksAsExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);

        $client->request('GET', $this->baseUrl . '/ROLE_ADMIN');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @dataProvider dataProviderTestThatGetInheritedRolesActionWorksAsExpected
     *
     * @param string $username
     * @param string $password
     */
    public function testThatGetInheritedRolesActionWorksAsExpected(string $username, string $password): void
    {
        $roles = ['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED'];

        $client = $this->getClient($username, $password);

        foreach ($roles as $role) {
            $offset = \array_search($role, $roles, true);
            $foo = \array_slice($roles, $offset);

            $client->request('GET', $this->baseUrl . '/' . $role . '/inherited');

            $response = $client->getResponse();

            static::assertInstanceOf(Response::class, $response);
            static::assertSame(200, $response->getStatusCode(), $response->getContent());
            static::assertJsonStringEqualsJsonString(JSON::encode($foo), $response->getContent());
        }
    }

    /**
     * @return array
     */
    public function dataProviderTestThatFindOneActionWorksAsExpected(): array
    {
        return [
            ['john-admin',  'password-admin'],
            ['john-root',   'password-root'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetInheritedRolesActionWorksAsExpected(): array
    {
        return [
            ['john',        'password'],
            ['john-logged', 'password-logged'],
            ['john-user',   'password-user'],
            ['john-admin',  'password-admin'],
            ['john-root',   'password-root'],
        ];
    }
}
