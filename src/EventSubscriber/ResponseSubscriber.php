<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/ResponseSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Service\Version;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class ResponseSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Version $version,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array{Symfony\Component\HttpKernel\Event\ResponseEvent: array{0: string, 1: int}}
     */
    #[ArrayShape([ResponseEvent::class => ['string', 'int']])]
    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => [
                'onKernelResponse',
                10,
            ],
        ];
    }

    /**
     * Subscriber method to attach API version to every response.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        // Attach new header
        $event->getResponse()->headers->add(['X-API-VERSION' => $this->version->get()]);
    }
}
