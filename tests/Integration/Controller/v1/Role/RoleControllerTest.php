<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Controller/v1/Role/RoleControllerTest.php
 */

namespace App\Tests\Integration\Controller\v1\Role;

use App\Controller\v1\Role\RoleController;
use App\Resource\RoleResource;
use App\Tests\Integration\TestCase\RestIntegrationControllerTestCase;

/**
 * @method RoleController getController()
 */
final class RoleControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var class-string
     */
    protected string $controllerClass = RoleController::class;

    /**
     * @var class-string
     */
    protected string $resourceClass = RoleResource::class;
}
