<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/RoleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Controller;

use App\Controller\RoleController;
use App\Resource\RoleResource;
use App\Utils\Tests\RestIntegrationControllerTestCase;

/**
 * Class RoleControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleControllerTest extends RestIntegrationControllerTestCase
{
    protected $controllerClass = RoleController::class;
    protected $resourceClass = RoleResource::class;
}
