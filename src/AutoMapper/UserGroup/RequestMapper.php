<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/UserGroup/RequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper\UserGroup;

use App\AutoMapper\RestRequestMapper;
use App\Entity\Role;
use App\Resource\RoleResource;
use Doctrine\ORM\ORMException;

/**
 * Class RequestMapper
 *
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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

    private RoleResource $roleResource;

    /**
     * RequestMapper constructor.
     */
    public function __construct(RoleResource $roleResource)
    {
        $this->roleResource = $roleResource;
    }

    /**
     * @throws ORMException
     */
    protected function transformRole(string $role): Role
    {
        return $this->roleResource->getReference($role);
    }
}
