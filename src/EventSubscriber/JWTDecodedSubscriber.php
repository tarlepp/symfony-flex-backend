<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/JWTDecodedSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use function array_key_exists;
use function hash;
use function implode;

/**
 * Class JWTDecodedSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTDecodedSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    /**
     * JWTDecodedSubscriber constructor.
     *
     * @param RequestStack    $requestStack
     * @param LoggerInterface $logger
     */
    public function __construct(RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
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
     * @return array<string, string> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JWTDecodedEvent::class => 'onJWTDecoded',
        ];
    }

    /**
     * Subscriber method to make some custom JWT payload checks.
     *
     * This method is called when 'lexik_jwt_authentication.on_jwt_decoded' event is broadcast.
     *
     * @param JWTDecodedEvent $event
     */
    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        // No need to continue event is invalid
        if (!$event->isValid()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        $this->checkPayload($event, $request);

        if ($request === null) {
            $this->logger->error('Request not available');

            $event->markAsInvalid();
        }
    }

    /**
     * Method to check payload data.
     *
     * @param JWTDecodedEvent $event
     * @param Request|null    $request
     */
    private function checkPayload(JWTDecodedEvent $event, ?Request $request): void
    {
        if ($request === null) {
            return;
        }

        $payload = $event->getPayload();

        // Get bits for checksum calculation
        $bits = [
            $request->getClientIp(),
            $request->headers->get('User-Agent'),
        ];

        // Calculate checksum
        $checksum = hash('sha512', implode('|', $bits));

        // Custom checks to validate user's JWT
        if (!array_key_exists('checksum', $payload) || $payload['checksum'] !== $checksum) {
            $event->markAsInvalid();
        }
    }
}
