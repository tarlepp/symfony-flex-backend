<?php
declare(strict_types = 1);
/**
 * /src/Utils/LoginLogger.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils;

use App\Entity\LogLogin;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Resource\LogLoginResource;
use DeviceDetector\DeviceDetector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class LoginLogger
 *
 * @package App\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLogger implements LoginLoggerInterface
{
    /**
     * @var LogLoginResource
     */
    private $logLoginResource;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var User
     */
    private $user;

    /**
     * @var DeviceDetector
     */
    private $deviceDetector;

    /**
     * LoginLogger constructor.
     *
     * @param LogLoginResource $logLoginFailureResource
     * @param UserRepository   $userRepository
     * @param RequestStack     $requestStack
     */
    public function __construct(
        LogLoginResource $logLoginFailureResource,
        UserRepository $userRepository,
        RequestStack $requestStack
    ) {
        // Store used services
        $this->logLoginResource = $logLoginFailureResource;
        $this->userRepository = $userRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * Setter for User object
     *
     * @param UserInterface|null $user
     *
     * @return LoginLoggerInterface
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function setUser(UserInterface $user = null): LoginLoggerInterface
    {
        if ($user !== null) {
            // We need to make sure that User object is right one
            $user = $user instanceof User ? $user : $this->userRepository->loadUserByUsername($user->getUsername());

            $this->user = $user;
        }

        return $this;
    }

    /**
     * Method to handle login event.
     *
     * @param string $type
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function process(string $type): void
    {
        // Get current request
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new \UnexpectedValueException('Could not get request from current request stack');
        }

        // Specify user agent
        $agent = $request->headers->get('User-Agent');

        // Parse user agent data with device detector
        $this->deviceDetector = new DeviceDetector($agent);
        $this->deviceDetector->parse();

        // Create entry
        $this->createEntry($type, $request, $agent);
    }

    /**
     * Method to create new login entry and store it to database.
     *
     * @param string  $type
     * @param Request $request
     * @param string  $agent
     *
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    private function createEntry(string $type, Request $request, string $agent): void
    {
        /** @var LogLogin $entry */
        $entry = new LogLogin();
        $entry->setType($type);
        $entry->setUser($this->user);
        $entry->setIp((string)$request->getClientIp());
        $entry->setHost($request->getHost());
        $entry->setAgent($agent);
        $entry->setClientType($this->deviceDetector->getClient('type'));
        $entry->setClientName($this->deviceDetector->getClient('name'));
        $entry->setClientShortName($this->deviceDetector->getClient('short_name'));
        $entry->setClientVersion($this->deviceDetector->getClient('version'));
        $entry->setClientEngine($this->deviceDetector->getClient('engine'));
        $entry->setOsName($this->deviceDetector->getOs('name'));
        $entry->setOsShortName($this->deviceDetector->getOs('short_name'));
        $entry->setOsVersion($this->deviceDetector->getOs('version'));
        $entry->setOsPlatform($this->deviceDetector->getOs('platform'));
        $entry->setDeviceName($this->deviceDetector->getDeviceName());
        $entry->setBrandName($this->deviceDetector->getBrandName());
        $entry->setModel($this->deviceDetector->getModel());
        $entry->setTimestamp(new \DateTime('NOW', new \DateTimeZone('UTC')));

        // And store entry to database
        $this->logLoginResource->save($entry, true);
    }
}
