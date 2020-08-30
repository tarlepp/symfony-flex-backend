<?php
declare(strict_types = 1);
/**
 * /src/Security/Roles.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security;

use App\Security\Interfaces\RolesServiceInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use function array_key_exists;
use function array_unique;
use function array_values;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;

/**
 * Class Roles
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RolesService implements RolesServiceInterface
{
    /**
     * Roles hierarchy.
     *
     * @var array<string, array<int, string>>
     */
    private array $rolesHierarchy;

    /**
     * @var array<string, string>
     */
    private static array $roleNames = [
        self::ROLE_LOGGED => 'Logged in users',
        self::ROLE_USER => 'Normal users',
        self::ROLE_ADMIN => 'Admin users',
        self::ROLE_ROOT => 'Root users',
        self::ROLE_API => 'API users',
    ];

    /**
     * RolesHelper constructor.
     *
     * @param array<string, array<int, string>> $rolesHierarchy
     */
    public function __construct(array $rolesHierarchy)
    {
        $this->rolesHierarchy = $rolesHierarchy;
    }

    public function getHierarchy(): array
    {
        return $this->rolesHierarchy;
    }

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

    public function getRoleLabel(string $role): string
    {
        $output = 'Unknown - ' . $role;

        if (array_key_exists($role, self::$roleNames)) {
            $output = self::$roleNames[$role];
        }

        return $output;
    }

    public function getShort(string $role): string
    {
        $offset = mb_strpos($role, '_');
        $offset = $offset !== false ? $offset + 1 : 0;

        return mb_strtolower(mb_substr($role, $offset));
    }

    public function getInheritedRoles(array $roles): array
    {
        return array_values(array_unique((new RoleHierarchy($this->rolesHierarchy))->getReachableRoleNames($roles)));
    }
}
