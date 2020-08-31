<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/JWTCreatedSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Security\SecurityUser;
use App\Service\Localization;
use DateTime;
use DateTimeZone;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use function hash;
use function implode;

/**
 * Class JWTCreatedSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTCreatedSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    /**
     * JWTCreatedListener constructor.
     */
    public function __construct(RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JWTCreatedEvent::class => 'onJWTCreated',
            Events::JWT_CREATED => 'onJWTCreated',
        ];
    }

    /**
     * Subscriber method to attach some custom data to current JWT payload.
     *
     * This method is called when following event is broadcast;
     *  - lexik_jwt_authentication.on_jwt_created
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        // Get current original payload
        $payload = $event->getData();

        // Set localization data
        $this->setLocalizationData($payload, $event->getUser());

        // Update JWT expiration data
        $this->setExpiration($payload);

        // Add some extra security data to payload
        $this->setSecurityData($payload);

        // And set new payload for JWT
        $event->setData($payload);
    }

    private function setLocalizationData(array &$payload, UserInterface $user): void
    {
        $payload['language'] = $user instanceof SecurityUser ? $user->getLanguage() : Localization::DEFAULT_LANGUAGE;
        $payload['locale'] = $user instanceof SecurityUser ? $user->getLocale() : Localization::DEFAULT_LOCALE;
        $payload['timezone'] = $user instanceof SecurityUser ? $user->getTimezone() : Localization::DEFAULT_TIMEZONE;
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Method to set/modify JWT expiration date dynamically.
     *
     * @param array<string, string|int> $payload
     */
    private function setExpiration(array &$payload): void
    {
        // Set new exp value for JWT
        /* @noinspection PhpUnhandledExceptionInspection */
        $payload['exp'] = (new DateTime('+1 day', new DateTimeZone('UTC')))->getTimestamp();
    }

    /**
     * Method to add some security related data to JWT payload, which are checked on JWT decode process.
     *
     * @see JWTDecodedListener
     *
     * @param array<string, string|int> $payload
     */
    private function setSecurityData(array &$payload): void
    {
        // Get current request
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            $this->logger->alert('Request not available');

            return;
        }

        // Get bits for checksum calculation
        $bits = [
            $request->getClientIp(),
            $request->headers->get('User-Agent'),
        ];

        // Attach checksum to JWT payload
        $payload['checksum'] = hash('sha512', implode('|', $bits));
    }
}
