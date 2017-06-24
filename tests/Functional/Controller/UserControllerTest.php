<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/UserControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserControllerTest
 *
 * @package App\Tests\Functional\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserControllerTest extends WebTestCase
{
    private $baseUrl = '/user';

    public function testThatGetBaseRouteReturn403(): void
    {
        $client = static::createClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());
        static::assertSame('{"message":"Access denied.","code":403,"status":401}', $response->getContent());
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @param string $username
     * @param string $password
     */
    public function testThatCountActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('{"count":5}', $response->getContent());
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @param string $username
     * @param string $password
     */
    public function testThatCountActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode());
        static::assertSame('{"message":"Access denied.","code":403,"status":403}', $response->getContent());
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @param string $username
     * @param string $password
     */
    public function testThatFindActionReturnsExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertCount(5, JSON::decode($response->getContent()));
    }

    /**
     * @dataProvider dataProviderInvalidUsers
     *
     * @param string $username
     * @param string $password
     */
    public function testThatFindActionReturns403ForInvalidUser(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(403, $response->getStatusCode());
        static::assertSame('{"message":"Access denied.","code":403,"status":403}', $response->getContent());
    }

    /**
     * @dataProvider dataProviderValidUsers
     *
     * @param string $username
     * @param string $password
     */
    public function testThatIdsActionReturnExpected(string $username, string $password): void
    {
        $client = $this->getClient($username, $password);
        $client->request('GET', $this->baseUrl . '/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertCount(5, JSON::decode($response->getContent()));
    }

    /**
     * @return array
     */
    public function dataProviderValidUsers(): array
    {
        return [
            ['john-admin', 'password-admin'],
            ['john-root',  'password-root'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderInvalidUsers(): array
    {
        return [
            ['john',        'password'],
            ['john-logged', 'password-logged'],
            ['john-user',   'password-user'],
        ];
    }
}
