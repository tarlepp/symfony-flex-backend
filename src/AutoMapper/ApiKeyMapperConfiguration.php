<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/ApiKeyMapperConfiguration.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper;

use App\DTO\ApiKey;
use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApiKeyMapperConfiguration
 *
 * @package App\AutoMapper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyMapperConfiguration implements AutoMapperConfiguratorInterface
{
    /**
     * @var ApiKeyRequestMapper
     */
    private $apiKeyRequestMapper;

    /**
     * ApiKeyMapperConfiguration constructor.
     *
     * @param ApiKeyRequestMapper $apiKeyRequestMapper
     */
    public function __construct(ApiKeyRequestMapper $apiKeyRequestMapper)
    {
        $this->apiKeyRequestMapper = $apiKeyRequestMapper;
    }

    /**
     * Use this method to register your mappings.
     *
     * @param AutoMapperConfigInterface $config
     */
    public function configure(AutoMapperConfigInterface $config): void
    {
        $config
            ->registerMapping(Request::class, ApiKey::class)
            ->useCustomMapper($this->apiKeyRequestMapper);
    }
}
