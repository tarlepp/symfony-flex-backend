<?php
declare(strict_types = 1);
/**
 * /src/Security/RolesInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security;

/**
 * Interface RolesInterface
 *
 * @package Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
     * @param mixed[] $rolesHierarchy This is a 'security.role_hierarchy.roles' parameter value
     */
    public function __construct(array $rolesHierarchy);

    /**
     * Getter for role hierarchy.
     *
     * @return mixed[]
     */
    public function getHierarchy(): array;

    /**
     * Getter method to return all roles in single dimensional array.
     *
     * @return string[]
     */
    public function getRoles(): array;

    /**
     * Getter method for role label.
     *
     * @param string $role
     *
     * @return string
     */
    public function getRoleLabel(string $role): string;

    /**
     * Getter method for short role.
     *
     * @param string $role
     *
     * @return string
     */
    public function getShort(string $role): string;

    /**
     * @param string[] $roles
     *
     * @return string[]
     */
    public function getInheritedRoles(array $roles): array;
}
