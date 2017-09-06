<?php
declare(strict_types=1);
/**
 * /src/Security/Roles.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Security;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

/**
 * Class Roles
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RolesService implements RolesServiceInterface
{
    /**
     * Roles hierarchy.
     *
     * @var array
     */
    private $rolesHierarchy;

    /**
     * RolesHelper constructor.
     *
     * @param array $rolesHierarchy This is a 'security.role_hierarchy.roles' parameter value
     */
    public function __construct(array $rolesHierarchy)
    {
        $this->rolesHierarchy = $rolesHierarchy;
    }

    /**
     * Getter for role hierarchy.
     *
     * @return array
     */
    public function getHierarchy(): array
    {
        return $this->rolesHierarchy;
    }

    /**
     * Getter method to return all roles in single dimensional array.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = [
            self::ROLE_LOGGED,
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_ROOT
        ];

        return $roles;
    }

    /**
     * Getter method for role label.
     *
     * @param string $role
     *
     * @return string
     */
    public function getRoleLabel(string $role): string
    {
        switch ($role) {
            case self::ROLE_LOGGED:
                $output = 'Logged in users';
                break;
            case self::ROLE_USER:
                $output = 'Normal users';
                break;
            case self::ROLE_ADMIN:
                $output = 'Admin users';
                break;
            case self::ROLE_ROOT:
                $output = 'Root users';
                break;
            default:
                $output = 'Unknown - ' . $role;
                break;
        }

        return $output;
    }

    /**
     * Getter method for short role.
     *
     * @param string $role
     *
     * @return string
     */
    public function getShort(string $role): string
    {
        return \mb_strtolower(\mb_substr($role, \mb_strpos($role, '_') + 1));
    }

    /**
     * Helper method to get inherited roles for given roles.
     *
     * @param array $roles
     *
     * @return array
     */
    public function getInheritedRoles(array $roles): array
    {
        $hierarchy = new RoleHierarchy($this->rolesHierarchy);

        return \array_unique(
            \array_map(
                function (Role $role) {
                    return $role->getRole();
                },
                $hierarchy->getReachableRoles(\array_map(
                    function (string $role) {
                        return new Role($role);
                    },
                    $roles
                ))
            )
        );
    }
}
