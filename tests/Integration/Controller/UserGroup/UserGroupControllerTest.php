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
 */
class UserGroupControllerTest extends RestIntegrationControllerTestCase
{
    protected string $controllerClass = UserGroupController::class;
    protected string $resourceClass = UserGroupResource::class;
}
