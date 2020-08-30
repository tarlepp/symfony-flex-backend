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
use function array_map;

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
        'username',
        'firstName',
        'lastName',
        'email',
        'language',
        'locale',
        'timezone',
        'userGroups',
        'password',
    ];

    private UserGroupResource $userGroupResource;

    /**
     * RequestMapper constructor.
     */
    public function __construct(UserGroupResource $userGroupResource)
    {
        $this->userGroupResource = $userGroupResource;
    }

    /**
     * @param array<int, string> $userGroups
     *
     * @return array<int, UserGroup>
     */
    protected function transformUserGroups(array $userGroups): array
    {
        return array_map(
            fn (string $userGroupUuid): UserGroup => $this->userGroupResource->getReference($userGroupUuid),
            $userGroups
        );
    }
}
