<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/ResponseSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Utils\JSON;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ResponseSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResponseSubscriber implements EventSubscriberInterface
{
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
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                'onKernelResponse',
                10,
            ],
        ];
    }

    /**
     * Subscriber method to log every response.
     *
     * @param FilterResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $response = $event->getResponse();

        // Attach new header
        $response->headers->add(['X-API-VERSION' => $this->getApiVersion()]);
    }

    /**
     * Method to get current version from composer.json file.
     *
     * @return string
     *
     * @throws \LogicException
     */
    private function getApiVersion(): string
    {
        return JSON::decode(\file_get_contents(__DIR__ . '/../../composer.json'))->version;
    }
}
