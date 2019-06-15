<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/User/RequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper\User;

use App\AutoMapper\RestRequestMapper;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Closure;
use function array_map;

/**
 * Class RequestMapper
 *
 * @package App\AutoMapper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestMapper extends RestRequestMapper
{
    protected static $properties = [
        'username',
        'firstName',
        'lastName',
        'email',
        'userGroups',
        'password',
    ];

    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * RequestMapper constructor.
     *
     * @param UserGroupResource $userGroupResource
     */
    public function __construct(UserGroupResource $userGroupResource)
    {
        $this->userGroupResource = $userGroupResource;
    }

    /**
     * @param array|array<int, string> $userGroups
     *
     * @return array|UserGroup[]
     */
    protected function transformUserGroups(array $userGroups): array
    {
        return array_map($this->getUserGroupReference(), $userGroups);
    }

    /**
     * @return Closure
     */
    private function getUserGroupReference(): Closure
    {
        return function (string $userGroupUuid): UserGroup {
            return $this->userGroupResource->getReference($userGroupUuid);
        };
    }
}
