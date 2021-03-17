<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/ApiKeyControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller;

use App\Controller\ApiKeyController;
use App\Resource\ApiKeyResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class ApiKeyControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method ApiKeyController getController()
 */
class ApiKeyControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var class-string
     */
    protected string $controllerClass = ApiKeyController::class;

    /**
     * @var class-string
     */
    protected string $resourceClass = ApiKeyResource::class;
}
