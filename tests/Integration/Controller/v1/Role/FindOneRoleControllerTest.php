<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/Role/FindOneRoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\Role;

use App\Controller\v1\Role\FindOneRoleController;
use App\Resource\RoleResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class FindOneRoleControllerTest
 *
 * @package App\Tests\Integration\Controller\v1\Role
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
