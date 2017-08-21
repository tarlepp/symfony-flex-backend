<?php
declare(strict_types=1);
/**
 * /src/Utils/LoginLoggerInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils;

use App\Repository\UserRepository;
use App\Resource\LogLoginFailureResource;
use App\Resource\LogLoginSuccessResource;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

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
    );

    /**
     * Setter for User object
     *
     * @param UserInterface $user
     *
     * @return LoginLoggerInterface
     *
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function setUser(UserInterface $user): LoginLoggerInterface;

    /**
     * Method to handle login event.
     *
     * @param string $type
     *
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function process(string $type): void;
}
