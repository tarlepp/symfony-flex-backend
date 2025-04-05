<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/UserGroup/UserGroupControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\UserGroup;

use App\Controller\v1\UserGroup\UserGroupController;
use App\Resource\UserGroupResource;
use App\Tests\Integration\TestCase\RestIntegrationControllerTestCase;

/**
 * @package App\Tests\Integration\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method \App\Controller\v1\UserGroup\UserGroupController getController()
 */
final class UserGroupControllerTest extends RestIntegrationControllerTestCase
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
