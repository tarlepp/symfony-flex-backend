<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AuthenticationSuccessSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Utils\LoginLogger;
use BadMethodCallException;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnexpectedValueException;

/**
 * Class AuthenticationSuccessSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationSuccessSubscriber implements EventSubscriberInterface
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
     * @codeCoverageIgnore
     *
     * @return mixed[] The event names to listen to
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
     * @psalm-suppress MissingDependency
     *
     * @param AuthenticationSuccessEvent $event
     *
     * @throws BadMethodCallException
     * @throws UnexpectedValueException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $this->loginLogger->setUser($event->getUser());
        $this->loginLogger->process(EnumLogLoginType::TYPE_SUCCESS);
    }
}
