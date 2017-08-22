<?php
declare(strict_types=1);
/**
 * /src/Utils/LoginLogger.php
 *
 * @Book  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils;

use App\Entity\EntityInterface;
use App\Entity\LogLoginFailure;
use App\Entity\LogLoginSuccess;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Resource\LogLoginFailureResource;
use App\Resource\LogLoginSuccessResource;
use App\Rest\RestResourceInterface;
use DeviceDetector\DeviceDetector;
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
    const TYPE_SUCCESS = 'success';
    const TYPE_FAILURE = 'failure';

    /**
     * @var LogLoginSuccessResource
     */
    private $logLoginSuccessResource;

    /**
     * @var LogLoginFailureResource
     */
    private $logLoginFailureResource;

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
     * @var string
     */
    private $agent;

    /**
     * @var DeviceDetector
     */
    private $deviceDetector;

    /**
     * LoginLogger constructor.
     *
     * @param LogLoginSuccessResource $logLoginSuccessResource
     * @param LogLoginFailureResource $logLoginFailureResource
     * @param UserRepository          $userRepository
     * @param RequestStack            $requestStack
     */
    public function __construct(
        LogLoginSuccessResource $logLoginSuccessResource,
        LogLoginFailureResource $logLoginFailureResource,
        UserRepository $userRepository,
        RequestStack $requestStack
    ) {
        // Store used services
        $this->logLoginSuccessResource = $logLoginSuccessResource;
        $this->logLoginFailureResource = $logLoginFailureResource;
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
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function process(string $type): void
    {
        // Specify user agent
        $this->agent = $this->requestStack->getCurrentRequest()->headers->get('User-Agent');

        // Parse user agent data with device detector
        $this->deviceDetector = new DeviceDetector($this->agent);
        $this->deviceDetector->parse();

        // Create entry
        $this->createEntry($type);
    }

    /**
     * Method to create new login entry and store it to database.
     *
     * @param string $type
     *
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    private function createEntry(string $type): void
    {
        // Get current request
        $request= $this->requestStack->getCurrentRequest();

        /** @var LogLoginSuccess|LogLoginFailure $entry */
        $entry = $this->getEntity($type);
        $entry->setUser($this->user);
        $entry->setIp((string)$request->getClientIp());
        $entry->setHost($request->getHost());
        $entry->setAgent($this->agent);
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
        $this->getResource($type)->save($entry, true);
    }

    /**
     * @param string $type
     *
     * @return LogLoginFailure|LogLoginSuccess|EntityInterface
     */
    private function getEntity(string $type): EntityInterface
    {
        return $type === self::TYPE_SUCCESS ? new LogLoginSuccess() : new LogLoginFailure();
    }

    /**
     * @param string $type
     *
     * @return LogLoginFailureResource|LogLoginSuccessResource|RestResourceInterface
     */
    private function getResource(string $type): RestResourceInterface
    {
        return $type ===  self::TYPE_SUCCESS ? $this->logLoginSuccessResource : $this->logLoginFailureResource;
    }
}
