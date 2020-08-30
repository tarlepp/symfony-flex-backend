<?php
declare(strict_types = 1);
/**
 * /src/Security/Interfaces/RolesInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security\Interfaces;

/**
 * Interface RolesInterface
 *
 * @package Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
     * RolesHelper constructor.
     *
     * @param array<string, array<int, string>> $rolesHierarchy
     */
    public function __construct(array $rolesHierarchy);

    /**
     * Getter for role hierarchy.
     *
     * @return array<string, array<int, string>>
     */
    public function getHierarchy(): array;

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
