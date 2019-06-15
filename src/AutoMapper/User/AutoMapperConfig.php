<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/UserMapperConfiguration.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper\User;

use App\DTO\User\UserCreate;
use App\DTO\User\UserPatch;
use App\DTO\User\UserUpdate;
use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AutoMapperConfig
 *
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AutoMapperConfig implements AutoMapperConfiguratorInterface
{
    /**
     * @var RequestMapper
     */
    private $userRequestMapper;

    /**
     * AutoMapperConfig constructor.
     *
     * @param RequestMapper $userRequestMapper
     */
    public function __construct(RequestMapper $userRequestMapper)
    {
        $this->userRequestMapper = $userRequestMapper;
    }

    /**
     * Use this method to register your mappings.
     *
     * @param AutoMapperConfigInterface $config
     */
    public function configure(AutoMapperConfigInterface $config): void
    {
        $config
            ->registerMapping(Request::class, UserCreate::class)
            ->useCustomMapper($this->userRequestMapper);

        $config
            ->registerMapping(Request::class, UserUpdate::class)
            ->useCustomMapper($this->userRequestMapper);

        $config
            ->registerMapping(Request::class, UserPatch::class)
            ->useCustomMapper($this->userRequestMapper);
    }
}
