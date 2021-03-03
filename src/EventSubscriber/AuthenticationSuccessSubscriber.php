<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AuthenticationSuccessSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Repository\UserRepository;
use App\Utils\LoginLogger;
use JetBrains\PhpStorm\ArrayShape;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

/**
 * Class AuthenticationSuccessSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoginLogger $loginLogger,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array{
     *      Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent: string,
     *      'lexik_jwt_authentication.on_authentication_success': string,
     *  }
     */
    #[ArrayShape([
        AuthenticationSuccessEvent::class => 'string',
        Events::AUTHENTICATION_SUCCESS => 'string',
    ])]
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    /**
     * Method to log user successfully login to database.
     *
     * This method is called when following event is broadcast
     *  - lexik_jwt_authentication.on_authentication_success
     *
     * @throws Throwable
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $this->loginLogger
            ->setUser($this->userRepository->loadUserByUsername($event->getUser()->getUsername(), true))
            ->process(EnumLogLoginType::TYPE_SUCCESS);
    }
}
