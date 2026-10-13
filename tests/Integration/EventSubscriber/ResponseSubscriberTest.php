<?php
declare(strict_types = 1);

/**
 * /tests/Integration/EventSubscriber/ResponseSubscriberTest.php
 */

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\ResponseSubscriber;
use App\Service\Version;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;

final class ResponseSubscriberTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `ResponseSubscriber` adds expected `X-API-VERSION` header to response')]
    public function testThatSubscriberAddsHeader(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $cacheMock = $this->createMock(CacheInterface::class);
        $loggerStub = self::createStub(LoggerInterface::class);

        $cacheMock
            ->expects($this->once())
            ->method('get')
            ->willReturn('1.2.3');

        $request = new Request();
        $response = new Response();

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $version = new Version($kernel->getProjectDir(), $cacheMock, $loggerStub);

        new ResponseSubscriber($version)
            ->onKernelResponse($event);

        $version = $event->getResponse()
            ->headers->get('X-API-VERSION');

        self::assertNotNull($version);
        self::assertSame('1.2.3', $version);
    }
}
