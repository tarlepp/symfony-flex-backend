<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/ApiKey/ApiKeyControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\ApiKey;

use App\Controller\v1\ApiKey\ApiKeyController;
use App\Resource\ApiKeyResource;
use App\Tests\Integration\TestCase\RestIntegrationControllerTestCase;

/**
 * @package App\Tests\Integration\Controller\v1
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method ApiKeyController getController()
 */
final class ApiKeyControllerTest extends RestIntegrationControllerTestCase
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
