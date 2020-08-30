<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/UserGroup/AutoMapperConfiguration.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper\UserGroup;

use App\AutoMapper\RestAutoMapperConfiguration;
use App\AutoMapper\RestRequestMapper;
use App\DTO\UserGroup\UserGroupCreate;
use App\DTO\UserGroup\UserGroupPatch;
use App\DTO\UserGroup\UserGroupUpdate;

/**
 * Class AutoMapperConfiguration
 *
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AutoMapperConfiguration extends RestAutoMapperConfiguration
{
    /**
     * Classes to use specified request mapper.
     *
     * @var array<int, string>
     */
    protected static array $requestMapperClasses = [
        UserGroupCreate::class,
        UserGroupUpdate::class,
        UserGroupPatch::class,
    ];

    protected RestRequestMapper $requestMapper;

    /**
     * AutoMapperConfiguration constructor.
     */
    public function __construct(RequestMapper $requestMapper)
    {
        $this->requestMapper = $requestMapper;
    }
}
