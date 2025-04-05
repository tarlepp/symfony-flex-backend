<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/User/UserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\User;

use App\Controller\v1\User\UserController;
use App\Resource\UserResource;
use App\Tests\Integration\TestCase\RestIntegrationControllerTestCase;

/**
 * @package App\Tests\Integration\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method UserController getController()
 */
final class UserControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var class-string
     */
    protected string $controllerClass = UserController::class;

    /**
     * @var class-string
     */
    protected string $resourceClass = UserResource::class;
}
