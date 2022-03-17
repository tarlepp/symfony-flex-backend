<?php
declare(strict_types = 1);
/**
 * /src/Security/RolesService.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security;

use App\Security\Interfaces\RolesServiceInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use function array_unique;
use function array_values;

/**
 * Class RolesService
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RolesService implements RolesServiceInterface
{
    public function __construct(
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function getInheritedRoles(array $roles): array
    {
        return array_values(array_unique($this->roleHierarchy->getReachableRoleNames($roles)));
    }
}
