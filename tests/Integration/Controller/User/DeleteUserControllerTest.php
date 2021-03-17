<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/User/DeleteUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\User;

use App\Controller\User\DeleteUserController;
use App\Resource\UserResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class DeleteUserControllerTest
 *
 * @package App\Tests\Integration\Controller\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method DeleteUserController getController()
 */
class DeleteUserControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var class-string
     */
    protected string $controllerClass = DeleteUserController::class;

    /**
     * @var class-string
     */
    protected string $resourceClass = UserResource::class;
}
