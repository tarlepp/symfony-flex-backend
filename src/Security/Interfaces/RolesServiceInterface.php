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
 * @package Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface RolesServiceInterface
{
    public function __construct(RoleHierarchyInterface $roleHierarchy);

    /**
     * Getter method to return all roles in single dimensional array.
     *
     * @return array<int, non-empty-string>
     */
    public function getRoles(): array;

    /**
     * Getter method for role label.
     *
     * @return non-empty-string
     */
    public function getRoleLabel(string $role): string;

    /**
     * Getter method for short role.
     *
     * @return non-empty-string
     */
    public function getShort(string $role): string;

    /**
     * Helper method to get inherited roles for given roles.
     *
     * @param array<int, string> $roles
     *
     * @return array<int, non-empty-string>
     */
    public function getInheritedRoles(array $roles): array;
}
