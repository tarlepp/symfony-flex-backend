<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AuthenticationFailureSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Repository\UserRepository;
use App\Utils\LoginLogger;
use Doctrine\ORM\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Throwable;

/**
 * Class AuthenticationFailureSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticationFailureSubscriber implements EventSubscriberInterface
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
     * @return mixed[] The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
        ];
    }

    /**
     * Method to log login failures to database.
     *
     * This method is called when '\Lexik\Bundle\JWTAuthenticationBundle\Events::AUTHENTICATION_FAILURE'
     * event is broadcast.
     *
     * @psalm-suppress MissingDependency
     *
     * @param AuthenticationFailureEvent $event
     *
     * @throws ORMException
     * @throws Throwable
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        // Fetch user entity
        if ($event->getException()->getToken() instanceof TokenInterface) {
            /** @var string $username */
            $username = $event->getException()->getToken()->getUser();

            $this->loginLogger->setUser($this->userRepository->loadUserByUsername($username));
        }

        $this->loginLogger->process(EnumLogLoginType::TYPE_FAILURE);
    }
}
