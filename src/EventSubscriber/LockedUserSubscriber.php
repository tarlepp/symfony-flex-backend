<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/LockedUserSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Entity\LogLoginFailure;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Resource\LogLoginFailureResource;
use App\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Throwable;
use function assert;
use function count;
use function is_string;

/**
 * Class LockedUserSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LockedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private LogLoginFailureResource $logLoginFailureResource,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string|array<int, string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => [
                'onAuthenticationSuccess',
                128,
            ],
            Events::AUTHENTICATION_SUCCESS => [
                'onAuthenticationSuccess',
                128,
            ],
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
        ];
    }

    /**
     * @throws Throwable
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $this->getUser($event->getUser()) ?? throw new UnsupportedUserException('Unsupported user.');

        if (count($user->getLogsLoginFailure()) > 10) {
            throw new LockedException('Locked account.');
        }

        $this->logLoginFailureResource->reset($user);
    }

    /**
     * @throws Throwable
     */
    public function onAuthenticationFailure(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        assert($request instanceof Request);

        $user = $this->getUser(
            (string)($request->query->get('username') ?? $request->request->get('username', ''))
        );

        if ($user !== null) {
            $this->logLoginFailureResource->save(new LogLoginFailure($user), true);
        }
    }

    /**
     * @throws Throwable
     */
    private function getUser(string | object $user): ?User
    {
        return match (true) {
            is_string($user) => $this->userRepository->loadUserByIdentifier($user, false),
            $user instanceof SecurityUser =>
                $this->userRepository->loadUserByIdentifier($user->getUserIdentifier(), true),
            default => null,
        };
    }
}
