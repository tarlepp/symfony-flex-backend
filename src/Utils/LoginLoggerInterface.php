<?php
declare(strict_types=1);
/**
 * /src/Utils/LoginLoggerInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils;

use App\Repository\UserRepository;
use App\Resource\LogLoginResource;
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
     * @param LogLoginResource $logLoginFailureResource
     * @param UserRepository   $userRepository
     * @param RequestStack     $requestStack
     */
    public function __construct(
        LogLoginResource $logLoginFailureResource,
        UserRepository $userRepository,
        RequestStack $requestStack
    );

    /**
     * Setter for User object
     *
     * @param UserInterface|null $user
     *
     * @return LoginLoggerInterface
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function setUser(UserInterface $user = null): LoginLoggerInterface;

    /**
     * Method to handle login event.
     *
     * @param string $type
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function process(string $type): void;
}
