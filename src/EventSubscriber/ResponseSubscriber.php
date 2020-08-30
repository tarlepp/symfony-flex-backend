<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/ResponseSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Service\Version;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class ResponseSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResponseSubscriber implements EventSubscriberInterface
{
    private Version $version;

    /**
     * ResponseSubscriber constructor.
     */
    public function __construct(Version $version)
    {
        $this->version = $version;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, array<int, string|int>>
     */
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
