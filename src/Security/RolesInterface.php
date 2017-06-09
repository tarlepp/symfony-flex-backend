<?php
declare(strict_types=1);
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
interface RolesInterface
{
    // Used role constants
    const ROLE_LOGGED   = 'ROLE_LOGGED';
    const ROLE_USER     = 'ROLE_USER';
    const ROLE_ADMIN    = 'ROLE_ADMIN';
    const ROLE_ROOT     = 'ROLE_ROOT';

    /**
     * RolesHelper constructor.
     *
     * @param array $rolesHierarchy This is a 'security.role_hierarchy.roles' parameter value
     */
    public function __construct(array $rolesHierarchy);

    /**
     * Getter for role hierarchy.
     *
     * @return array
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
     * @param array $roles
     *
     * @return array
     */
    public function getInheritedRoles(array $roles): array;
}
