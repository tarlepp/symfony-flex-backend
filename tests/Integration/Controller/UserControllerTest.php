<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/UserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Controller;

use App\Controller\UserController;
use App\Resource\UserResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class UserControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserControllerTest extends RestIntegrationControllerTestCase
{
    protected $controllerClass = UserController::class;
    protected $resourceClass = UserResource::class;
}
