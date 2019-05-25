<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventSubscriber/RequestSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\ResponseSubscriber;
use App\Utils\JSON;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;
use function file_get_contents;

/**
 * Class ResponseSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResponseSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatSubscriberAddsHeader(): void
    {
        static::bootKernel();

        $request = new Request();
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        $subscriber = new ResponseSubscriber();
        $subscriber->onKernelResponse($event);

        $response = $event->getResponse();

        /** @noinspection NullPointerExceptionInspection */
        $version = $response->headers->get('X-API-VERSION');

        static::assertNotNull($version);
        static::assertSame(JSON::decode(file_get_contents(__DIR__ . '/../../../composer.json'))->version, $version);

        unset($response, $subscriber, $event, $request);
    }
}
