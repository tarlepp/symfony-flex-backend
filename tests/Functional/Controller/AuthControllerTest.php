<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/AuthControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
     * @dataProvider dataProviderTestThatLoginRouteDoesNotAllowOtherThanPost
     *
     * @param string $method
     */
    public function testThatLoginRouteDoesNotAllowOtherThanPost(string $method): void
    {
        $client = static::createClient();
        $client->request($method, $this->baseUrl . '/getToken');

        static::assertSame(405, $client->getResponse()->getStatusCode());
    }

    public function testThatLoginRouteReturn401WithInvalidCredentials(): void
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

        static::assertSame(401, $client->getResponse()->getStatusCode());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatLoginRouteDoesNotAllowOtherThanPost(): array
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
}
