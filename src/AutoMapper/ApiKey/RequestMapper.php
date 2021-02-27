<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/ApiKey/RequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\AutoMapper\ApiKey;

use App\AutoMapper\RestRequestMapper;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Throwable;
use function array_map;

/**
 * Class RequestMapper
 *
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * Properties to map to destination object.
     *
     * @var array<int, string>
     */
    protected static array $properties = [
        'description',
        'userGroups',
    ];

    public function __construct(
        private UserGroupResource $userGroupResource,
    ) {
    }

    /**
     * @param array<int, string> $userGroups
     *
     * @return array<int, UserGroup>
     *
     * @throws Throwable
     */
    protected function transformUserGroups(array $userGroups): array
    {
        return array_map(
            fn (string $userGroupUuid): UserGroup => $this->userGroupResource->getReference($userGroupUuid),
            $userGroups,
        );
    }
}
