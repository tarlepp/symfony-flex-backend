<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/ResponseSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Utils\JSON;
use stdClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function file_get_contents;

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
     * @return mixed[] The event names to listen to
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
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // Attach new header
        $response->headers->add(['X-API-VERSION' => $this->getApiVersion()]);
    }

    /**
     * Method to get current version from composer.json file.
     *
     * @return string
     */
    private function getApiVersion(): string
    {
        /** @var stdClass $data */
        $data = JSON::decode((string)file_get_contents(__DIR__ . '/../../composer.json'));

        return property_exists($data, 'version') ? (string)$data->version : 'unknown';
    }
}
