<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/LockedUserSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Entity\LogLoginFailure;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Resource\LogLoginFailureResource;
use App\Security\SecurityUser;
use Doctrine\ORM\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\Event as LexikBaseEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Throwable;
use function is_string;

/**
 * Class LockedUserSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LockedUserSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var LogLoginFailureResource
     */
    private $logLoginFailureResource;

    /**
     * @var bool
     */
    private $reset = true;

    /**
     * LockedUserSubscriber constructor.
     *
     * @param UserRepository          $userRepository
     * @param LogLoginFailureResource $logLoginFailureResource
     */
    public function __construct(UserRepository $userRepository, LogLoginFailureResource $logLoginFailureResource)
    {
        $this->userRepository = $userRepository;
        $this->logLoginFailureResource = $logLoginFailureResource;
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
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
        ];
    }

    /**
     * Method to increase
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
        $token = $event->getException()->getToken();

        if ($token === null) {
            return;
        }

        $user = $this->getUser($token->getUser());

        if ($user instanceof User) {
            $this->checkLockedAccount($user, $event);
        }
    }

    /**
     * Method to reset login failures for current user.
     *
     * This method is called when '\Symfony\Component\Security\Core\Event\AuthenticationEvent::AUTHENTICATION_SUCCESS'
     * event is broadcast.
     *
     * @psalm-suppress MissingDependency
     *
     * @param Event $event
     *
     * @throws ORMException
     * @throws Throwable
     */
    public function onAuthenticationSuccess(Event $event): void
    {
        if ($event instanceof AuthenticationSuccessEvent) {
            $user = $this->getUser($event->getUser());

            if ($user instanceof User) {
                $this->checkLockedAccount($user, $event);
            }
        }
    }

    /**
     * @psalm-suppress MissingDependency
     * @psalm-suppress MismatchingDocblockParamType
     *
     * @param User                                                                 $user
     * @param LexikBaseEvent|AuthenticationFailureEvent|AuthenticationSuccessEvent $event
     *
     * @throws Throwable
     */
    private function checkLockedAccount(User $user, LexikBaseEvent $event): void
    {
        if ($event instanceof AuthenticationFailureEvent) {
            $this->onAuthenticationFailureEvent($user);
        } elseif ($event instanceof AuthenticationSuccessEvent) {
            $this->onAuthenticationSuccessEvent($user);
        }
    }

    /**
     * @param User $user
     *
     * @throws Throwable
     */
    private function onAuthenticationFailureEvent(User $user): void
    {
        $this->logLoginFailureResource->save(new LogLoginFailure($user));
    }

    /**
     * @param User $user
     */
    private function onAuthenticationSuccessEvent(User $user): void
    {
        if ($this->reset === true) {
            $this->logLoginFailureResource->reset($user);
        }
    }

    /**
     * @param string|UserInterface|mixed $user
     *
     * @return User|null
     *
     * @throws ORMException
     */
    private function getUser($user): ?User
    {
        if (is_string($user)) {
            $user = $this->userRepository->loadUserByUsername($user);
        } elseif ($user instanceof SecurityUser) {
            $user = $this->userRepository->loadUserByUsername($user->getUsername());
        }

        return $user instanceof User ? $user : null;
    }
}
