<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/JWTDecodedSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
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
    private $requestStack;

    /**
     * @var LoggerInterface
     */
    private $logger;

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

        if ($request === null) {
            $this->logger->error('Request not available');

            $event->markAsInvalid();
        } else {
            $this->checkPayload($event, $request);
        }
    }

    /**
     * Method to check payload data.
     *
     * @param JWTDecodedEvent $event
     * @param Request         $request
     */
    private function checkPayload(JWTDecodedEvent $event, Request $request): void
    {
        $payload = $event->getPayload();

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
