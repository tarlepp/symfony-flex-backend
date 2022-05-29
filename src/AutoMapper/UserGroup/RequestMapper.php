<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/UserGroup/RequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\AutoMapper\UserGroup;

use App\AutoMapper\RestRequestMapper;
use App\Entity\Role;
use App\Resource\RoleResource;
use Throwable;

/**
 * Class RequestMapper
 *
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, string>
     */
    protected static array $properties = [
        'name',
        'role',
    ];

    public function __construct(
        private readonly RoleResource $roleResource,
    ) {
    }

    /**
     * @throws Throwable
     */
    protected function transformRole(string $role): Role
    {
        return $this->roleResource->getReference($role);
    }
}
