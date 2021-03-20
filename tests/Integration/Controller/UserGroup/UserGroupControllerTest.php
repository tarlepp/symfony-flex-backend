<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/UserGroup/UserGroupControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\UserGroup;

use App\Controller\UserGroup\UserGroupController;
use App\Resource\UserGroupResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class UserGroupControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method UserGroupController getController()
 */
class UserGroupControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var class-string
     */
    protected string $controllerClass = UserGroupController::class;

    /**
     * @var class-string
     */
    protected string $resourceClass = UserGroupResource::class;
}
