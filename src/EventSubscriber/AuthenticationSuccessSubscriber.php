<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AuthenticationSuccessSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

/**
 * Class AuthenticationSuccessSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationSuccessSubscriber
{
    /**
     * @var LoginLogger
     */
    protected $loginLogger;

    /**
     * AuthenticationSuccessListener constructor.
     *
     * @param LoginLogger $loginLogger
     */
    public function __construct(LoginLogger $loginLogger)
    {
        $this->loginLogger = $loginLogger;
    }

    /**
     * Method to log user successfully login to database.
     *
     * This method is called when 'lexik_jwt_authentication.on_authentication_success' event is broadcast.
     *
     * @param AuthenticationSuccessEvent $event
     *
     * @throws \UnexpectedValueException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $this->loginLogger->setUser($event->getUser());
        $this->loginLogger->process(EnumLogLoginType::TYPE_SUCCESS);
    }
}
