<?php
declare(strict_types=1);
/**
 * /tests/Integration/Controller/ApiKeyControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Controller;

use App\Controller\ApiKeyController;
use App\Resource\ApiKeyResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class ApiKeyControllerTest
 *
 * @package Integration\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var string
     */
    protected $controllerClass = ApiKeyController::class;

    /**
     * @var string
     */
    protected $resourceClass = ApiKeyResource::class;
}
