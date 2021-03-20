<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/ResponseSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\ResponseSubscriber;
use App\Service\Version;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;

/**
 * Class ResponseSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ResponseSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `ResponseSubscriber` adds expected `X-API-VERSION` header to response
     */
    public function testThatSubscriberAddsHeader(): void
    {
        static::bootKernel();

        $cacheStub = $this->createMock(CacheInterface::class);
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $cacheStub
            ->expects(static::once())
            ->method('get')
            ->willReturn('1.2.3');

        $request = new Request();
        $response = new Response();

        $event = new ResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
        $version = new Version(static::$kernel->getProjectDir(), $cacheStub, $logger);

        (new ResponseSubscriber($version))
            ->onKernelResponse($event);

        $version = $event->getResponse()->headers->get('X-API-VERSION');

        static::assertNotNull($version);
        static::assertSame('1.2.3', $version);
    }
}
