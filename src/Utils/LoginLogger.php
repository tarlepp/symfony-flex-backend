<?php
declare(strict_types = 1);
/**
 * /src/Utils/LoginLogger.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Utils;

use App\Entity\LogLogin;
use App\Entity\User;
use App\Enum\Login;
use App\Resource\LogLoginResource;
use App\Utils\Interfaces\LoginLoggerInterface;
use BadMethodCallException;
use DeviceDetector\DeviceDetector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * Class LoginLogger
 *
 * @package App\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoginLogger implements LoginLoggerInterface
{
    private DeviceDetector $deviceDetector;
    private ?User $user = null;

    public function __construct(
        private LogLoginResource $logLoginResource,
        private RequestStack $requestStack,
    ) {
        $this->deviceDetector = new DeviceDetector();
    }

    public function setUser(?User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function process(Login $type): void
    {
        // Get current request
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new BadMethodCallException('Could not get request from current request stack');
        }

        // Parse user agent data with device detector
        $this->deviceDetector->setUserAgent($request->headers->get('User-Agent', ''));
        $this->deviceDetector->parse();

        // Create entry
        $this->createEntry($type, $request);
    }

    /**
     * Method to create new login entry and store it to database.
     *
     * @throws Throwable
     */
    private function createEntry(Login $type, Request $request): void
    {
        $entry = new LogLogin($type, $request, $this->deviceDetector, $this->user);

        // And store entry to database
        $this->logLoginResource->save($entry, true);
    }
}
