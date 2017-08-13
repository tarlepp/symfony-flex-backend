<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventSubscriber/RequestSubscriberTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\RequestSubscriber;
use App\Utils\RequestLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class RequestSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestSubscriberTest extends KernelTestCase
{
    public function testThatMethodCallsExpectedLoggerMethods(): void
    {
        static::bootKernel();

        $request = new Request();
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|RequestLogger $logger
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage
         */
        $logger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        $logger
            ->expects(static::once())
            ->method('setRequest')
            ->with($request)
            ->willReturn($logger);

        $logger
            ->expects(static::once())
            ->method('setResponse')
            ->with($event->getResponse())
            ->willReturn($logger);

        $logger
            ->expects(static::once())
            ->method('setUser')
            ->with(null)
            ->willReturn($logger);

        $logger
            ->expects(static::once())
            ->method('setMasterRequest')
            ->with($event->isMasterRequest())
            ->willReturn($logger);

        $logger
            ->expects(static::once())
            ->method('handle');

        $subscriber = new RequestSubscriber($logger, $tokenStorage);
        $subscriber->onKernelResponse($event);
    }

    public function testThatLoggerServiceIsNotCalledIfOptionsRequest(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'OPTIONS']);
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|RequestLogger $logger
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage
         */
        $logger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        $logger
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($logger);

        $subscriber = new RequestSubscriber($logger, $tokenStorage);
        $subscriber->onKernelResponse($event);
    }

    public function testThatLoggerServiceIsNotCalledIfHealthzRequest(): void
    {
        static::bootKernel();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/healthz']);
        $response = new Response();

        $event = new FilterResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|RequestLogger $logger
         * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage
         */
        $logger = $this->getMockBuilder(RequestLogger::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        $logger
            ->expects(static::never())
            ->method('setRequest')
            ->with($request)
            ->willReturn($logger);

        $subscriber = new RequestSubscriber($logger, $tokenStorage);
        $subscriber->onKernelResponse($event);
    }
}
