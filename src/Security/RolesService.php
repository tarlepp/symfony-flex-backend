<?php
declare(strict_types = 1);
/**
 * /src/Security/Roles.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use function array_key_exists;
use function array_map;
use function array_unique;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;

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
     * @var mixed[]
     */
    private $rolesHierarchy;

    /**
     * @var mixed[]
     */
    private static $roleNames = [
        self::ROLE_LOGGED => 'Logged in users',
        self::ROLE_USER => 'Normal users',
        self::ROLE_ADMIN => 'Admin users',
        self::ROLE_ROOT => 'Root users',
        self::ROLE_API => 'API users',
    ];

    /**
     * RolesHelper constructor.
     *
     * @param mixed[] $rolesHierarchy This is a 'security.role_hierarchy.roles' parameter value
     */
    public function __construct(array $rolesHierarchy)
    {
        $this->rolesHierarchy = $rolesHierarchy;
    }

    /**
     * Getter for role hierarchy.
     *
     * @return mixed[]
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
        return [
            self::ROLE_LOGGED,
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_ROOT,
            self::ROLE_API,
        ];
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
        $output = 'Unknown - ' . $role;

        if (array_key_exists($role, self::$roleNames)) {
            $output = self::$roleNames[$role];
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
        $offset = mb_strpos($role, '_');
        $offset = $offset !== false ? $offset + 1 : 0;

        return mb_strtolower(mb_substr($role, $offset));
    }

    /**
     * Helper method to get inherited roles for given roles.
     *
     * @param string[] $roles
     *
     * @return string[]
     */
    public function getInheritedRoles(array $roles): array
    {
        $hierarchy = new RoleHierarchy($this->rolesHierarchy);

        return array_unique(
            array_map(
                static function (Role $role): string {
                    return $role->getRole();
                },
                $hierarchy->getReachableRoles(array_map(
                    static function (string $role): Role {
                        return new Role($role);
                    },
                    $roles
                ))
            )
        );
    }
}
