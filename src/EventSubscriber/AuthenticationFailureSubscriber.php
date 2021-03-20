<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AuthenticationFailureSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Repository\UserRepository;
use App\Utils\LoginLogger;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use function is_string;

/**
 * Class AuthenticationFailureSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AuthenticationFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoginLogger $loginLogger,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
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

        // Fetch user entity
        if ($token !== null && is_string($token->getUser())) {
            /** @var string $username */
            $username = $token->getUser();

            $this->loginLogger->setUser($this->userRepository->loadUserByUsername($username, false));
        }

        $this->loginLogger->process(EnumLogLoginType::TYPE_FAILURE);
    }
}
