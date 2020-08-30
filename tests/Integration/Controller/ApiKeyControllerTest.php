<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/ApiKeyControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Controller;

use App\Controller\ApiKeyController;
use App\Resource\ApiKeyResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class ApiKeyControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property ApiKeyController $controller
 */
class ApiKeyControllerTest extends RestIntegrationControllerTestCase
{
    protected string $controllerClass = ApiKeyController::class;
    protected string $resourceClass = ApiKeyResource::class;
}
