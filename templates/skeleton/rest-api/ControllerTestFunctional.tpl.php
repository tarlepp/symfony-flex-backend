<?= "<?php\n" ?>
declare(strict_types = 1);
/**
 * /tests/Functional/Controller/<?= $controllerName ?>Test.php
 *
 * @author  <?= $author . "\n" ?>
 */
namespace App\Tests\Functional\Controller;

use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class <?= $controllerName ?>Test
 *
 * @package App\Tests\Functional\Controller
 * @author  <?= $author . "\n" ?>
 */
class <?= $controllerName ?>Test extends WebTestCase
{
    private $baseUrl = '<?= $routePath ?>';

    /**
     * @throws \Exception
     */
    public function testThatGetBaseRouteReturn401(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());
    }
}
