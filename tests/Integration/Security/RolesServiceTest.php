<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/RolesServiceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security;

use App\Security\RolesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Class RolesServiceTest
 *
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RolesServiceTest extends KernelTestCase
{
    /**
     * @testdox Test that `getInheritedRoles(array $roles)` method calls expected service method
     */
    public function testThatGetInheritedRolesMethodCallsExpectedServiceMethod(): void
    {
        $roleHierarchy = $this->getMockBuilder(RoleHierarchyInterface::class)->getMock();

        $roleHierarchy
            ->expects(self::once())
            ->method('getReachableRoleNames')
            ->with(['RoleA', 'RoleB'])
            ->willReturn(['RoleA', 'RoleB', 'RoleC']);

        (new RolesService($roleHierarchy))->getInheritedRoles(['RoleA', 'RoleB']);
    }
}
