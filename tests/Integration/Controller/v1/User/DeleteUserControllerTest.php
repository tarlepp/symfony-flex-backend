<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/User/DeleteUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\User;

use App\Controller\v1\User\DeleteUserController;
use App\Resource\UserResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class DeleteUserControllerTest
 *
 * @package App\Tests\Integration\Controller\v1\User
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
