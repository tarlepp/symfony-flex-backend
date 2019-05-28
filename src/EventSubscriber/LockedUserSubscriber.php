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
use App\Security\ApiKeyUser;
use App\Security\SecurityUser;
use Doctrine\ORM\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;
use function count;
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
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            Events::JWT_AUTHENTICATED => 'onJWTAuthenticated',
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
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
        // Fetch user entity
        if ($event->getException()->getToken() instanceof TokenInterface) {
            $user = $this->getUser($event->getException()->getToken()->getUser());

            if ($user instanceof User) {
                $this->checkLockedAccount($user, $event);
            }
        }
    }

    /**
     * Method to check if current user account is in locked state or not.
     *
     * This method is called when '\Lexik\Bundle\JWTAuthenticationBundle\Events::JWTAuthenticatedEvent' event is
     * broadcast.
     *
     * @psalm-suppress MissingDependency
     *
     * @param JWTAuthenticatedEvent $event
     *
     * @throws ORMException
     * @throws Throwable
     */
    public function onJWTAuthenticated(JWTAuthenticatedEvent $event): void
    {
        $user = $this->getUser($event->getToken()->getUser());

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
     * @param AuthenticationEvent $event
     *
     * @throws ORMException
     * @throws Throwable
     */
    public function onAuthenticationSuccess(AuthenticationEvent $event): void
    {
        $user = $this->getUser($event->getAuthenticationToken()->getUser());

        if ($user instanceof User) {
            $this->checkLockedAccount($user, $event);
        }
    }

    /**
     * @psalm-suppress MissingDependency
     * @psalm-suppress MismatchingDocblockParamType
     *
     * @param User $user
     * @param Event|JWTAuthenticatedEvent|AuthenticationFailureEvent|AuthenticationEvent $event
     *
     * @throws Throwable
     */
    private function checkLockedAccount(User $user, Event $event): void
    {
        switch (true) {
            case $event instanceof JWTAuthenticatedEvent:
                $this->onJWTAuthenticatedEvent($user, $event);
                break;
            case $event instanceof AuthenticationFailureEvent:
                $this->onAuthenticationFailureEvent($user);
                break;
            case $event instanceof AuthenticationEvent:
                $this->onAuthenticationEvent($user);
                break;
        }
    }

    /**
     * @psalm-suppress MissingDependency
     *
     * @param User                  $user
     * @param JWTAuthenticatedEvent $event
     */
    private function onJWTAuthenticatedEvent(User $user, JWTAuthenticatedEvent $event): void
    {
        if (count($user->getLogsLoginFailure()) > 10) {
            $event->getToken()->setAuthenticated(false);

            $this->reset = false;
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
    private function onAuthenticationEvent(User $user): void
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
        if ($user instanceof ApiKeyUser) {
            $user = null;
        } elseif (is_string($user)) {
            $user = $this->userRepository->loadUserByUsername($user);
        } elseif ($user instanceof SecurityUser) {
            $user = $this->userRepository->loadUserByUsername($user->getUsername());
        }

        return $user ?? null;
    }
}
