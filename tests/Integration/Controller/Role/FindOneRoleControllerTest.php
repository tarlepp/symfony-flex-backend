<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/Role/FindOneRoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\Role;

use App\Controller\Role\FindOneRoleController;
use App\Resource\RoleResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class FindOneRoleControllerTest
 *
 * @package App\Tests\Integration\Controller\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method FindOneRoleController getController()
 */
class FindOneRoleControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var class-string
     */
    protected string $controllerClass = FindOneRoleController::class;

    /**
     * @var class-string
     */
    protected string $resourceClass = RoleResource::class;
}
