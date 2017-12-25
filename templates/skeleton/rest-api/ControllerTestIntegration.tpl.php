<?= "<?php\n" ?>
declare(strict_types=1);
/**
 * /tests/Integration/Controller/<?= $controllerName ?>Test.php
 *
 * @author  <?= $author . "\n" ?>
 */
namespace App\Tests\Integration\Controller;

use App\Controller\<?= $controllerName ?>;
use App\Resource\<?= $resourceName ?>;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class <?= $controllerName ?>Test
 *
 * @package Integration\Controller
 * @author  <?= $author . "\n" ?>
 */
class <?= $controllerName ?>Test extends RestIntegrationControllerTestCase
{
    /**
     * @var string
     */
    protected $controllerClass = <?= $controllerName ?>::class;

    /**
     * @var string
     */
    protected $resourceClass = <?= $resourceName ?>::class;
}
