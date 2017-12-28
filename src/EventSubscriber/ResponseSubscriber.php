<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/ResponseSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Utils\JSON;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class ResponseSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResponseSubscriber
{
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
