<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/Role/InheritedRolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\Role;

use App\Controller\v1\Role\InheritedRolesController;
use App\Entity\Role;
use App\Security\RolesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class InheritedRolesControllerTest
 *
 * @package App\Tests\Integration\Controller\v1\Role
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class InheritedRolesControllerTest extends KernelTestCase
{
    /**
     * @testdox Test that `__invoke($role)` method calls expected service methods
     */
    public function testThatInvokeMethodCallsExpectedMethods(): void
    {
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();
        $role = new Role('Test');

        $rolesService
            ->expects(self::once())
            ->method('getInheritedRoles')
            ->with([$role->getId()])
            ->willReturn([$role]);

        (new InheritedRolesController($rolesService))($role);
    }
}
