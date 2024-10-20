<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AuthenticationFailureSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Enum\LogLogin;
use App\Repository\UserRepository;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

/**
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AuthenticationFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoginLogger $loginLogger,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
        ];
    }

    /**
     * Method to log login failures to database.
     *
     * This method is called when following event is broadcast;
     *  - \Lexik\Bundle\JWTAuthenticationBundle\Events::AUTHENTICATION_FAILURE
     *
     * @throws Throwable
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $token = $event->getException()->getToken();
        $user = $token?->getUser();

        // Fetch user entity
        if ($token !== null && $user !== null) {
            $identifier = $user->getUserIdentifier();

            $this->loginLogger->setUser($this->userRepository->loadUserByIdentifier($identifier, false));
        }

        $this->loginLogger->process(LogLogin::FAILURE);
    }
}
