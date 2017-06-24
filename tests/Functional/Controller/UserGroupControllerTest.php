<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/UserGroupControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserGroupControllerTest
 *
 * @package App\Tests\Functional\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupControllerTest extends WebTestCase
{
    private $baseUrl = '/user_group';

    public function testThatGetBaseRouteReturn403(): void
    {
        $client = static::createClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(401, $response->getStatusCode());
    }
}
