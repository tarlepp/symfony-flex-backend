<?php
declare(strict_types=1);
/**
 * /src/EventSubscriber/AuthenticationFailureSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Security\UserProvider;
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
     * @var UserProvider
     */
    protected $userProvider;

    /**
     * AuthenticationFailureSubscriber constructor.
     *
     * @param LoginLogger  $loginLogger
     * @param UserProvider $userProvider
     */
    public function __construct(LoginLogger $loginLogger, UserProvider $userProvider)
    {
        $this->loginLogger = $loginLogger;
        $this->userProvider = $userProvider;
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
        $user = $this->userProvider->loadUserByUsername($event->getException()->getToken()->getUser());

        $this->loginLogger->setUser($user);
        $this->loginLogger->process(EnumLogLoginType::TYPE_FAILURE);
    }
}
