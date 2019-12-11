<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AuthenticationSuccessSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Repository\UserRepository;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

/**
 * Class AuthenticationSuccessSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    private LoginLogger $loginLogger;
    private UserRepository $userRepository;

    /**
     * AuthenticationSuccessListener constructor.
     *
     * @param LoginLogger    $loginLogger
     * @param UserRepository $userRepository
     */
    public function __construct(LoginLogger $loginLogger, UserRepository $userRepository)
    {
        $this->loginLogger = $loginLogger;
        $this->userRepository = $userRepository;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array<string, string> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    /**
     * Method to log user successfully login to database.
     *
     * This method is called when 'lexik_jwt_authentication.on_authentication_success' event is broadcast.
     *
     * @param AuthenticationSuccessEvent $event
     *
     * @throws Throwable
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $this->loginLogger
            ->setUser($this->userRepository->loadUserByUsername($event->getUser()->getUsername()))
            ->process(EnumLogLoginType::TYPE_SUCCESS);
    }
}
