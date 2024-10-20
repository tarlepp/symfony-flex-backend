<?php
declare(strict_types = 1);
/**
 * /src/Security/RolesService.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security;

use App\Enum\Role;
use App\Security\Interfaces\RolesServiceInterface;
use BackedEnum;
use Override;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use function array_map;
use function array_unique;
use function array_values;
use function mb_strtolower;

/**
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RolesService implements RolesServiceInterface
{
    public function __construct(
        private readonly RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    #[Override]
    public function getRoles(): array
    {
        return array_map(static fn (BackedEnum $enum): string => $enum->value, Role::cases());
    }

    #[Override]
    public function getRoleLabel(string $role): string
    {
        $enum = Role::tryFrom($role);

        return $enum instanceof Role
            ? $enum->label()
            : 'Unknown - ' . $role;
    }

    #[Override]
    public function getShort(string $role): string
    {
        $enum = Role::tryFrom($role);

        return $enum instanceof Role
            ? mb_strtolower($enum->name)
            : 'Unknown - ' . $role;
    }

    #[Override]
    public function getInheritedRoles(array $roles): array
    {
        return array_values(array_unique($this->roleHierarchy->getReachableRoleNames($roles)));
    }
}
