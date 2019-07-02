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
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
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
            Events::AUTHENTICATION_SUCCESS => [
                'onAuthenticationSuccess',
                128,
            ],
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
        ];
    }

    /**
     * @param AuthenticationSuccessEvent $event
     *
     * @throws ORMException
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $this->getUser($event->getUser());

        if ($user === null) {
            throw new UnsupportedUserException('Unsupported user.');
        }

        if (count($user->getLogsLoginFailure()) > 10) {
            throw new LockedException('Locked account.');
        }

        $this->logLoginFailureResource->reset($user);
    }

    /**
     * @param AuthenticationFailureEvent $event
     *
     * @throws Throwable
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $token = $event->getException()->getToken();

        if ($token !== null) {
            $user = $this->getUser($token->getUser());

            if ($user !== null) {
                $this->logLoginFailureResource->save(new LogLoginFailure($user), true);
            }

            $token->setAuthenticated(false);
        }
    }

    /**
     * @param string|object $user
     *
     * @return User|null
     *
     * @throws ORMException
     */
    private function getUser($user): ?User
    {
        $output = null;

        if (is_string($user)) {
            $output = $this->userRepository->loadUserByUsername($user);
        } elseif ($user instanceof SecurityUser) {
            $output = $this->userRepository->loadUserByUsername($user->getUsername());
        }

        return $output;
    }
}
