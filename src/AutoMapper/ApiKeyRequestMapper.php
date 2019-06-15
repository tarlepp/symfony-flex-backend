<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/ApiKeyRequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper;

use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Closure;
use function array_map;

/**
 * Class ApiKeyRequestMapper
 *
 * @package App\AutoMapper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyRequestMapper extends RestRequestMapper
{
    /**
     * Properties to map to destination object.
     *
     * @var array
     */
    protected static $properties = [
        'description',
        'userGroups',
    ];

    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * ApiKeyRequestMapper constructor.
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
