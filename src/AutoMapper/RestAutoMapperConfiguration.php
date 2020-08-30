<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/RestAutoMapperConfiguration.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper;

use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RestAutoMapperConfiguration
 *
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestAutoMapperConfiguration implements AutoMapperConfiguratorInterface
{
    /**
     * Classes to use specified request mapper.
     *
     * @var array<int, string>
     */
    protected static array $requestMapperClasses = [];

    protected RestRequestMapper $requestMapper;

    /**
     * Use this method to register your mappings.
     */
    public function configure(AutoMapperConfigInterface $config): void
    {
        foreach (static::$requestMapperClasses as $requestMapperClass) {
            $config
                ->registerMapping(Request::class, $requestMapperClass)
                ->useCustomMapper($this->requestMapper);
        }
    }
}
