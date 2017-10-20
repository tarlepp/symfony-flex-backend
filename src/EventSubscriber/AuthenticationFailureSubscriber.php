<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AuthenticationFailureSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Entity\User;
use App\Entity\UserInterface;
use App\Repository\UserRepository;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;

/**
 * Class AuthenticationFailureSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationFailureSubscriber
{
    /**
     * @var LoginLogger
     */
    protected $loginLogger;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * AuthenticationFailureSubscriber constructor.
     *
     * @param LoginLogger  $loginLogger
     * @param UserRepository $userRepository
     */
    public function __construct(LoginLogger $loginLogger, UserRepository $userRepository)
    {
        $this->loginLogger = $loginLogger;
        $this->userRepository = $userRepository;
    }

    /**
     * Method to log login failures to database.
     *
     * This method is called when 'lexik_jwt_authentication.on_authentication_success' event is broadcast.
     *
     * @param AuthenticationFailureEvent $event
     *
     * @throws \UnexpectedValueException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        // Fetch user entity
        if ($event->getException()->getToken() !== null) {
            /** @var UserInterface $user */
            $user = $this->userRepository->loadUserByUsername($event->getException()->getToken()->getUser());

            $this->loginLogger->setUser($user);
        }

        $this->loginLogger->process(EnumLogLoginType::TYPE_FAILURE);
    }
}
