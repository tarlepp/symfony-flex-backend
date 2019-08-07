<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/BodySubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Utils\JSON;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use function fstat;
use function in_array;
use function is_array;
use function is_resource;
use function is_string;

/**
 * Class BodySubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class BodySubscriber implements EventSubscriberInterface
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
            'kernel.request' => [
                'onKernelRequest',
                10,
            ],
        ];
    }

    /**
     * Implementation of BodySubscriber event. Purpose of this is to convert JSON request data to proper request
     * parameters.
     *
     * @param RequestEvent $event
     *
     * @throws LogicException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Get current request
        $request = $event->getRequest();
        $content = $request->getContent();

        // Request content is empty so assume that it's ok - probably DELETE or OPTION request
        if ((is_string($content) && $content === '') || (is_resource($content) && fstat($content)['size'] === 0)) {
            return;
        }

        // If request is JSON type convert it to request parameters
        if ($this->isJsonRequest($request)) {
            $this->transformJsonBody($request);
        }
    }

    /**
     * Method to determine if current Request is JSON type or not.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isJsonRequest(Request $request): bool
    {
        return in_array($request->getContentType(), [null, 'json', 'txt'], true);
    }

    /**
     * Method to transform JSON type request to proper request parameters.
     *
     * @param Request $request
     *
     * @throws LogicException
     */
    private function transformJsonBody(Request $request): void
    {
        $data = null;
        $content = $request->getContent();

        if (is_string($content)) {
            $data = JSON::decode($content, true);
        }

        if (is_array($data)) {
            $request->request->replace($data);
        }
    }
}
