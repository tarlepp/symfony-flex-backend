<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/ApiKeyMapperConfiguration.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper\ApiKey;

use App\DTO\ApiKey\ApiKeyCreate;
use App\DTO\ApiKey\ApiKeyPatch;
use App\DTO\ApiKey\ApiKeyUpdate;
use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApiKeyMapperConfiguration
 *
 * @package App\AutoMapper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AutoMapperConfig implements AutoMapperConfiguratorInterface
{
    /**
     * @var RequestMapper
     */
    private $apiKeyRequestMapper;

    /**
     * ApiKeyMapperConfiguration constructor.
     *
     * @param RequestMapper $apiKeyRequestMapper
     */
    public function __construct(RequestMapper $apiKeyRequestMapper)
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
            ->registerMapping(Request::class, ApiKeyCreate::class)
            ->useCustomMapper($this->apiKeyRequestMapper);

        $config
            ->registerMapping(Request::class, ApiKeyUpdate::class)
            ->useCustomMapper($this->apiKeyRequestMapper);

        $config
            ->registerMapping(Request::class, ApiKeyPatch::class)
            ->useCustomMapper($this->apiKeyRequestMapper);
    }
}
