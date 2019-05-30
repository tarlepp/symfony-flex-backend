<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/UserGroupControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Controller;

use App\Controller\UserGroupController;
use App\Resource\UserGroupResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class UserGroupControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupControllerTest extends RestIntegrationControllerTestCase
{
    protected $controllerClass = UserGroupController::class;
    protected $resourceClass = UserGroupResource::class;
}
