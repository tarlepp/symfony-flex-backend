<?php
declare(strict_types=1);
/**
 * /src/Utils/LoginLogger.php
 *
 * @Book  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils;

use App\Entity\LogLoginSuccess;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Resource\LogLoginSuccessResource;
use DeviceDetector\DeviceDetector;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
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
     * @var Logger
     */
    private $logger;

    /**
     * @var LogLoginSuccessResource
     */
    private $loginLogResource;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Request
     */
    private $request;

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
     * @param LoggerInterface         $logger
     * @param LogLoginSuccessResource $loginLogResource
     * @param UserRepository          $userRepository
     * @param RequestStack            $requestStack
     */
    public function __construct(
        LoggerInterface $logger,
        LogLoginSuccessResource $loginLogResource,
        UserRepository $userRepository,
        RequestStack $requestStack
    ) {
        // Store used services
        $this->logger = $logger;
        $this->loginLogResource = $loginLogResource;
        $this->userRepository = $userRepository;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Setter for User object
     *
     * @param UserInterface $user
     *
     * @return LoginLoggerInterface
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function setUser(UserInterface $user): LoginLoggerInterface
    {
        // We need to make sure that User object is right one
        $user = $user instanceof User ? $user : $this->userRepository->loadUserByUsername($user->getUsername());

        $this->user = $user;

        return $this;
    }

    /**
     * Method to handle login event.
     *
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function handle(): void
    {
        // Specify user agent
        $this->agent = $this->request->headers->get('User-Agent');

        // Parse user agent data with device detector
        $this->deviceDetector = new DeviceDetector($this->agent);
        $this->deviceDetector->parse();

        // Create entry
        $this->createEntry();

        $this->logger->debug('Created new login entry to database.');
    }

    /**
     * Method to create new login entry and store it to database.
     *
     * @return LogLoginSuccess
     *
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    private function createEntry(): LogLoginSuccess
    {
        // Create new login entry
        $userLogin = new LogLoginSuccess();
        $userLogin->setUser($this->user);
        $userLogin->setIp((string)$this->request->getClientIp());
        $userLogin->setHost($this->request->getHost());
        $userLogin->setAgent($this->agent);
        $userLogin->setClientType($this->deviceDetector->getClient('type'));
        $userLogin->setClientName($this->deviceDetector->getClient('name'));
        $userLogin->setClientShortName($this->deviceDetector->getClient('short_name'));
        $userLogin->setClientVersion($this->deviceDetector->getClient('version'));
        $userLogin->setClientEngine($this->deviceDetector->getClient('engine'));
        $userLogin->setOsName($this->deviceDetector->getOs('name'));
        $userLogin->setOsShortName($this->deviceDetector->getOs('short_name'));
        $userLogin->setOsVersion($this->deviceDetector->getOs('version'));
        $userLogin->setOsPlatform($this->deviceDetector->getOs('platform'));
        $userLogin->setDeviceName($this->deviceDetector->getDeviceName());
        $userLogin->setBrandName($this->deviceDetector->getBrandName());
        $userLogin->setModel($this->deviceDetector->getModel());
        $userLogin->setTimestamp(new \DateTime('NOW', new \DateTimeZone('UTC')));

        // And store entry to database
        return $this->loginLogResource->save($userLogin, true);
    }
}
