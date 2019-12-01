<?php
declare(strict_types = 1);
/**
 * /src/Utils/LoginLogger.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils;

use App\Entity\LogLogin;
use App\Entity\User;
use App\Resource\LogLoginResource;
use BadMethodCallException;
use DeviceDetector\DeviceDetector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * Class LoginLogger
 *
 * @package App\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLogger implements LoginLoggerInterface
{
    private LogLoginResource $logLoginResource;
    private RequestStack $requestStack;
    private ?User $user;
    private DeviceDetector $deviceDetector;

    /**
     * LoginLogger constructor.
     *
     * @param LogLoginResource $logLoginFailureResource
     * @param RequestStack     $requestStack
     */
    public function __construct(LogLoginResource $logLoginFailureResource, RequestStack $requestStack)
    {
        // Store used services
        $this->logLoginResource = $logLoginFailureResource;
        $this->requestStack = $requestStack;
    }

    /**
     * Setter for User object
     *
     * @param User|null $user
     *
     * @return LoginLoggerInterface
     */
    public function setUser(?User $user = null): LoginLoggerInterface
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Method to handle login event.
     *
     * @param string $type
     *
     * @throws Throwable
     */
    public function process(string $type): void
    {
        // Get current request
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new BadMethodCallException('Could not get request from current request stack');
        }

        // Parse user agent data with device detector
        $this->deviceDetector = new DeviceDetector((string)$request->headers->get('User-Agent', ''));
        $this->deviceDetector->parse();

        // Create entry
        $this->createEntry($type, $request);
    }

    /**
     * Method to create new login entry and store it to database.
     *
     * @param string  $type
     * @param Request $request
     *
     * @throws Throwable
     */
    private function createEntry(string $type, Request $request): void
    {
        /** @var LogLogin $entry */
        $entry = new LogLogin($type, $request, $this->deviceDetector, $this->user);

        // And store entry to database
        $this->logLoginResource->save($entry, true);
    }
}
