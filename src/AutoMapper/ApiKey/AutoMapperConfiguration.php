<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/ApiKey/AutoMapperConfiguration.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper\ApiKey;

use App\AutoMapper\RestAutoMapperConfiguration;
use App\DTO\ApiKey\ApiKeyCreate;
use App\DTO\ApiKey\ApiKeyPatch;
use App\DTO\ApiKey\ApiKeyUpdate;

/**
 * Class AutoMapperConfiguration
 *
 * @package App\AutoMapper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AutoMapperConfiguration extends RestAutoMapperConfiguration
{
    /**
     * Classes to use specified request mapper.
     *
     * @var string[]
     */
    protected static $requestMapperClasses = [
        ApiKeyCreate::class,
        ApiKeyUpdate::class,
        ApiKeyPatch::class,
    ];

    /**
     * AutoMapperConfiguration constructor.
     *
     * @param RequestMapper $requestMapper
     */
    public function __construct(RequestMapper $requestMapper)
    {
        $this->requestMapper = $requestMapper;
    }
}
