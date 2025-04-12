<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/Role/RoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\Role;

use App\Controller\v1\Role\RoleController;
use App\Resource\RoleResource;
use App\Tests\Integration\TestCase\RestIntegrationControllerTestCase;

/**
 * @package App\Tests\Integration\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
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
