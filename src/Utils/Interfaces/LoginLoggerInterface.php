<?php
declare(strict_types = 1);
/**
 * /src/Utils/Interfaces/LoginLoggerInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Utils\Interfaces;

use App\Entity\User;
use App\Enum\LogLogin;
use App\Resource\LogLoginResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * @package App\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface LoginLoggerInterface
{
    public function __construct(LogLoginResource $logLoginFailureResource, RequestStack $requestStack);

    /**
     * Setter for User object (Entity).
     */
    public function setUser(?User $user = null): self;

    /**
     * Method to handle login event.
     *
     * @throws Throwable
     */
    public function process(LogLogin $type): void;
}
