<?php
declare(strict_types=1);
/**
 * /src/EventSubscriber/JWTDecodedSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class JWTDecodedSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTDecodedSubscriber
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

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
     * Subscriber method to make some custom JWT payload checks.
     *
     * This method is called when 'lexik_jwt_authentication.on_jwt_decoded' event is broadcast.
     *
     * @param JWTDecodedEvent $event
     */
    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        // Get current payload and request object
        $payload = $event->getPayload();
        $request = $this->requestStack->getCurrentRequest();

        // Get bits for checksum calculation
        $bits = [
            $request->getClientIp(),
            $request->headers->get('User-Agent'),
        ];

        // Calculate checksum
        $checksum = \hash('sha512', \implode('|', $bits));

        // Custom checks to validate user's JWT
        if (!\array_key_exists('checksum', $payload) || $payload['checksum'] !== $checksum) {
            $event->markAsInvalid();
        }
    }
}
