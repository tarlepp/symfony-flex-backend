<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/User/UserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\User;

use App\Controller\User\UserController;
use App\Resource\UserResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class UserControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserControllerTest extends RestIntegrationControllerTestCase
{
    protected string $controllerClass = UserController::class;
    protected string $resourceClass = UserResource::class;
}
