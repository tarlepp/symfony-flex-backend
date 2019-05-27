<?php
declare(strict_types = 1);
/**
 * /src/Utils/LoginLoggerInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils;

use App\Entity\User;
use App\Resource\LogLoginResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * Interface LoginLogger
 *
 * @package App\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface LoginLoggerInterface
{
    /**
     * LoginLogger constructor.
     *
     * @param LogLoginResource $logLoginFailureResource
     * @param RequestStack     $requestStack
     */
    public function __construct(LogLoginResource $logLoginFailureResource, RequestStack $requestStack);

    /**
     * Setter for User object
     *
     * @param User|null $user
     *
     * @return LoginLoggerInterface
     */
    public function setUser(?User $user = null): self;

    /**
     * Method to handle login event.
     *
     * @param string $type
     *
     * @throws Throwable
     */
    public function process(string $type): void;
}
