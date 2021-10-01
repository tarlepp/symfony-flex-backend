<?php
declare(strict_types = 1);
/**
 * /src/Security/Interfaces/RolesServiceInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security\Interfaces;

use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Interface RolesServiceInterface
 *
 * @package Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface RolesServiceInterface
{
    // Used role constants
    public const ROLE_LOGGED = 'ROLE_LOGGED';
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_ROOT = 'ROLE_ROOT';
    public const ROLE_API = 'ROLE_API';

    /**
     * RolesService constructor.
     */
    public function __construct(
        RoleHierarchyInterface $roleHierarchy,
    );

    /**
     * Getter method to return all roles in single dimensional array.
     *
     * @return array<int, string>
     */
    public function getRoles(): array;

    /**
     * Getter method for role label.
     */
    public function getRoleLabel(string $role): string;

    /**
     * Getter method for short role.
     */
    public function getShort(string $role): string;

    /**
     * Helper method to get inherited roles for given roles.
     *
     * @param array<int, string> $roles
     *
     * @return array<int, string>
     */
    public function getInheritedRoles(array $roles): array;
}
