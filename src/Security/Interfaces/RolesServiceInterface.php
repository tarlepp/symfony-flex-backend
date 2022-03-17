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

    public function __construct(
        RoleHierarchyInterface $roleHierarchy,
    );

    /**
     * Helper method to get inherited roles for given roles.
     *
     * @param array<int, string> $roles
     *
     * @return array<int, string>
     */
    public function getInheritedRoles(array $roles): array;
}
