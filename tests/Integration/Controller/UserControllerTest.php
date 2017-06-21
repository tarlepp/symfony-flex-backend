<?php
declare(strict_types=1);
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
 * @package Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserControllerTest extends RestIntegrationControllerTestCase
{
    /**
     * @var string
     */
    protected $controllerClass = UserController::class;

    /**
     * @var string
     */
    protected $resourceClass = UserResource::class;
}
