<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/JWTDecodedSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Helpers\LoggerAwareTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
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
    // Traits
    use LoggerAwareTrait;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * JWTDecodedSubscriber constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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
            Events::JWT_DECODED => 'onJWTDecoded',
        ];
    }

    /**
     * Subscriber method to make some custom JWT payload checks.
     *
     * This method is called when 'lexik_jwt_authentication.on_jwt_decoded' event is broadcast.
     *
     * @psalm-suppress MissingDependency
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
     * @psalm-suppress MissingDependency
     *
     * @param JWTDecodedEvent $event
     * @param Request|null    $request
     */
    private function checkPayload(JWTDecodedEvent $event, ?Request $request = null): void
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
