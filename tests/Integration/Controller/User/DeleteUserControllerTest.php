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
 */
class DeleteUserControllerTest extends RestIntegrationControllerTestCase
{
    protected string $controllerClass = DeleteUserController::class;
    protected string $resourceClass = UserResource::class;
}
