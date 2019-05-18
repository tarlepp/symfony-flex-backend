<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/<?php echo $controllerName ?>Test.php
 *
 * @author  <?php echo $author . "\n" ?>
 */
namespace App\Tests\Integration\Controller;

use App\Controller\<?php echo $controllerName ?>;
use App\Resource\<?php echo $resourceName ?>;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class <?php echo $controllerName ?>Test
 *
 * @package Integration\Controller
 * @author  <?php echo $author . "\n" ?>
 */
class <?php echo $controllerName ?>Test extends RestIntegrationControllerTestCase
{
    /**
     * @var string
     */
    protected $controllerClass = <?php echo $controllerName ?>::class;

    /**
     * @var string
     */
    protected $resourceClass = <?php echo $resourceName ?>::class;
}
