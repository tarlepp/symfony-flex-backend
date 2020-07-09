<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/User/AutoMapperConfiguration.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper\User;

use App\AutoMapper\RestAutoMapperConfiguration;
use App\AutoMapper\RestRequestMapper;
use App\DTO\User\UserCreate;
use App\DTO\User\UserPatch;
use App\DTO\User\UserUpdate;

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
        UserCreate::class,
        UserUpdate::class,
        UserPatch::class,
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
