<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /tests/Functional/Controller/<?php echo $controllerName ?>Test.php
 *
 * @author  <?php echo $author . "\n" ?>
 */
namespace App\Tests\Functional\Controller;

use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class <?php echo $controllerName ?>Test
 *
 * @package App\Tests\Functional\Controller
 * @author  <?php echo $author . "\n" ?>
 */
class <?php echo $controllerName ?>Test extends WebTestCase
{
    private $baseUrl = '<?php echo $routePath ?>';

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
