<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/Role/RoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\Role;

use App\Controller\Role\RoleController;
use App\Resource\RoleResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class RoleControllerTest
 *
 * @package App\Tests\Integration\Controller\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method RoleController getController()
 */
class RoleControllerTest extends RestIntegrationControllerTestCase
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
